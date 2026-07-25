<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'region_manager', 'gov_supervisor']);
$pageTitle   = 'لوحة المحافظة';
$activeRoute = 'governorate.php';

$pdo = db();
$role = $currentUserData['role_code'];

/* ------------------------------------------------------------
   تحديد المحافظة المعروضة
------------------------------------------------------------ */
if ($role === 'gov_supervisor') {
    $govId = (int)$currentUserData['governorate_id'];
    $govsList = [];
} else {
    $sql = "SELECT g.id, g.name, r.name AS region_name FROM governorates g JOIN regions r ON r.id = g.region_id";
    $params = [];
    if ($role === 'region_manager') {
        $sql .= " WHERE g.region_id = ?";
        $params[] = (int)$currentUserData['region_id'];
    }
    $sql .= " ORDER BY r.name, g.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $govsList = $stmt->fetchAll();
    $govId = isset($_GET['gov_id']) ? (int)$_GET['gov_id'] : (int)($govsList[0]['id'] ?? 0);
}

if (!$govId) {
    require __DIR__ . '/../../includes/header.php';
    echo '<div class="panel"><div class="placeholder-box">لا توجد محافظات ضمن نطاقك بعد.</div></div>';
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT g.*, r.name AS region_name FROM governorates g JOIN regions r ON r.id = g.region_id WHERE g.id = ?");
$stmt->execute([$govId]);
$govInfo = $stmt->fetch();

/* ------------------------------------------------------------
   موانئ المحافظة
------------------------------------------------------------ */
$stmt = $pdo->prepare("SELECT id, name, is_active FROM ports WHERE governorate_id = ? ORDER BY name");
$stmt->execute([$govId]);
$govPorts = $stmt->fetchAll();
$portIds = array_column($govPorts, 'id') ?: [0];
$ph = implode(',', array_fill(0, count($portIds), '?'));

/* ------------------------------------------------------------
   المؤشرات الرئيسية
------------------------------------------------------------ */
$kpi = ['ports' => count($govPorts)];

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT ea.employee_id) FROM employee_assignments ea
     WHERE ea.port_id IN ($ph) AND ea.assignment_date = CURDATE()"
);
$stmt->execute($portIds);
$kpi['employees'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT ea.employee_id) FROM employee_assignments ea
     JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
     WHERE ea.port_id IN ($ph) AND ea.assignment_date = CURDATE() AND a.status IN ('present','late')"
);
$stmt->execute($portIds);
$kpi['present'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id IN ($ph) AND status = 'expected'");
$stmt->execute($portIds);
$kpi['expected'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id IN ($ph) AND status IN ('arrived','waiting_employee')");
$stmt->execute($portIds);
$kpi['arrived'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id IN ($ph) AND status = 'counting'");
$stmt->execute($portIds);
$kpi['counting'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id IN ($ph) AND status IN ('approved','closed') AND DATE(actual_arrival) = CURDATE()");
$stmt->execute($portIds);
$kpi['approved'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT td.trip_id) FROM trip_discrepancies td JOIN trips t ON t.id = td.trip_id
     WHERE t.port_id IN ($ph) AND td.review_status != 'approved'"
);
$stmt->execute($portIds);
$kpi['diff_trips'] = (int)$stmt->fetchColumn();

/* ------------------------------------------------------------
   توزيع الرحلات حسب الميناء + مقارنة الموانئ
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT p.id, p.name,
            COUNT(t.id) AS trips_today,
            COALESCE(SUM(CASE WHEN t.status IN ('approved','closed') THEN t.verified_weight ELSE 0 END),0) AS approved_weight
     FROM ports p
     LEFT JOIN trips t ON t.port_id = p.id AND DATE(t.actual_arrival) = CURDATE()
     WHERE p.id IN ($ph)
     GROUP BY p.id, p.name ORDER BY trips_today DESC"
);
$stmt->execute($portIds);
$portComparison = $stmt->fetchAll();

/* ------------------------------------------------------------
   المناوبات الحالية + الموظفون المتاحون/المشغولون
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT u.full_name, p.name AS port_name, s.name AS shift_name, a.status AS attendance_status,
            (SELECT COUNT(*) FROM trips t WHERE t.assigned_employee_id = ea.employee_id AND t.status IN ('waiting_employee','counting')) AS active_trip_count
     FROM employee_assignments ea
     JOIN employees e ON e.id = ea.employee_id
     JOIN users u ON u.id = e.user_id
     JOIN ports p ON p.id = ea.port_id
     JOIN shifts s ON s.id = ea.shift_id
     LEFT JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
     WHERE ea.port_id IN ($ph) AND ea.assignment_date = CURDATE()
     ORDER BY s.start_time, u.full_name"
);
$stmt->execute($portIds);
$shiftStaff = $stmt->fetchAll();

$availableStaff = array_filter($shiftStaff, fn($r) => ($r['attendance_status'] ?? null) === 'present' && (int)$r['active_trip_count'] === 0);
$busyStaff      = array_filter($shiftStaff, fn($r) => ($r['attendance_status'] ?? null) === 'present' && (int)$r['active_trip_count'] > 0);

/* ------------------------------------------------------------
   الرحلات المتأخرة (بانتظار أكثر من 30 دقيقة بدون بدء إحصاء)
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT t.trip_code, p.name AS port_name, t.actual_arrival
     FROM trips t JOIN ports p ON p.id = t.port_id
     WHERE t.port_id IN ($ph) AND t.status IN ('arrived','waiting_employee')
       AND t.actual_arrival <= (NOW() - INTERVAL 30 MINUTE)
     ORDER BY t.actual_arrival"
);
$stmt->execute($portIds);
$delayedTrips = $stmt->fetchAll();

/* ------------------------------------------------------------
   تنبيهات الغياب والازدحام
------------------------------------------------------------ */
$govAlerts = [];
foreach ($govPorts as $p) {
    if (!$p['is_active']) continue;
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM employee_assignments ea
         JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
         WHERE ea.port_id = ? AND ea.assignment_date = CURDATE() AND a.status = 'absent'"
    );
    $stmt->execute([$p['id']]);
    $absentCount = (int)$stmt->fetchColumn();
    if ($absentCount > 0) {
        $govAlerts[] = ['port' => $p['name'], 'message' => "$absentCount موظف غائب اليوم", 'severity' => 'warning'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id = ? AND status IN ('arrived','waiting_employee','counting')");
    $stmt->execute([$p['id']]);
    $activeCount = (int)$stmt->fetchColumn();
    if ($activeCount >= 3) {
        $govAlerts[] = ['port' => $p['name'], 'message' => "ازدحام: $activeCount قوارب نشطة حاليًا", 'severity' => 'critical'];
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if (!empty($govsList) && count($govsList) > 1): ?>
<form method="get" class="panel" style="display:flex; gap:12px; align-items:center; padding:14px 18px;">
    <label style="font-weight:700; font-size:13.5px;">المحافظة:</label>
    <select name="gov_id" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid var(--line);">
        <?php foreach ($govsList as $g): ?>
            <option value="<?= (int)$g['id'] ?>" <?= $g['id'] == $govId ? 'selected' : '' ?>><?= e($g['name']) ?> — <?= e($g['region_name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">موانئ المحافظة</span><span class="stat-value"><?= numberAr($kpi['ports']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون</span><span class="stat-value"><?= numberAr($kpi['employees']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الحاضرون</span><span class="stat-value"><?= numberAr($kpi['present']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب المتوقعة</span><span class="stat-value"><?= numberAr($kpi['expected']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب الواصلة</span><span class="stat-value"><?= numberAr($kpi['arrived']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">تحت الإحصاء</span><span class="stat-value"><?= numberAr($kpi['counting']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">تم اعتمادها اليوم</span><span class="stat-value"><?= numberAr($kpi['approved']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">بها فروقات</span><span class="stat-value"><?= numberAr($kpi['diff_trips']) ?></span></div>
</div>

<div class="grid-2">
    <div class="panel">
        <h3>توزيع الرحلات ومقارنة الموانئ (اليوم)</h3>
        <?php if (empty($portComparison)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>الميناء</th><th>رحلات اليوم</th><th>الإنتاج المعتمد (كجم)</th></tr></thead>
            <tbody>
            <?php foreach ($portComparison as $p): ?>
                <tr><td><?= e($p['name']) ?></td><td class="num"><?= numberAr($p['trips_today']) ?></td><td class="num"><?= numberAr($p['approved_weight']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>المناوبات الحالية</h3>
        <?php if (empty($shiftStaff)): ?><p class="panel-hint">لا يوجد موظفون مسندون اليوم.</p><?php else: ?>
        <table>
            <thead><tr><th>الموظف</th><th>الميناء</th><th>المناوبة</th><th>الحالة</th></tr></thead>
            <tbody>
            <?php foreach ($shiftStaff as $s): ?>
                <tr>
                    <td><?= e($s['full_name']) ?></td>
                    <td><?= e($s['port_name']) ?></td>
                    <td><?= e($s['shift_name']) ?></td>
                    <td><?= e($s['attendance_status'] ?? 'لم يبدأ') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <h3>الموظفون المتاحون والمشغولون</h3>
        <p class="panel-hint">متاحون: <?= numberAr(count($availableStaff)) ?> — مشغولون: <?= numberAr(count($busyStaff)) ?></p>
        <?php if (empty($shiftStaff)): ?>
            <p class="panel-hint">لا يوجد موظفون مسندون اليوم.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الموظف</th><th>الميناء</th><th>الحالة</th></tr></thead>
            <tbody>
            <?php foreach ($shiftStaff as $s):
                $present = ($s['attendance_status'] ?? null) === 'present';
                $busy = $present && (int)$s['active_trip_count'] > 0;
                $lbl = !$present ? 'غير متاح' : ($busy ? 'مشغول' : 'متاح');
                $cls = !$present ? 'badge-muted' : ($busy ? 'badge-warning' : 'badge-success');
            ?>
                <tr><td><?= e($s['full_name']) ?></td><td><?= e($s['port_name']) ?></td><td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>الرحلات المتأخرة</h3>
        <?php if (empty($delayedTrips)): ?>
            <p class="panel-hint">لا توجد رحلات متأخرة حاليًا. ✅</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الرحلة</th><th>الميناء</th><th>وقت الوصول</th></tr></thead>
            <tbody>
            <?php foreach ($delayedTrips as $t): ?>
                <tr><td><?= e($t['trip_code']) ?></td><td><?= e($t['port_name']) ?></td><td><?= e($t['actual_arrival']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <h3>تنبيهات الغياب والازدحام</h3>
    <?php if (empty($govAlerts)): ?>
        <p class="panel-hint">لا توجد تنبيهات حاليًا. ✅</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الميناء</th><th>التنبيه</th><th>الخطورة</th></tr></thead>
        <tbody>
        <?php foreach ($govAlerts as $a): ?>
            <tr>
                <td><?= e($a['port']) ?></td>
                <td><?= e($a['message']) ?></td>
                <td><span class="badge <?= $a['severity'] === 'critical' ? 'badge-danger' : 'badge-warning' ?>"><?= $a['severity'] === 'critical' ? 'حرج' : 'تحذير' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
