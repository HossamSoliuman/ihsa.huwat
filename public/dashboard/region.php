<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'region_manager']);
$pageTitle   = 'لوحة المنطقة';
$activeRoute = 'region.php';

$pdo = db();
$role = $currentUserData['role_code'];

$regionsList = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();

if ($role === 'region_manager') {
    $regionId = (int)$currentUserData['region_id'];
} else {
    $regionId = isset($_GET['region_id']) ? (int)$_GET['region_id'] : (int)($regionsList[0]['id'] ?? 0);
}

if (!$regionId) {
    require __DIR__ . '/../../includes/header.php';
    echo '<div class="panel"><div class="placeholder-box">لا توجد مناطق مسجّلة بعد.</div></div>';
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

/* ------------------------------------------------------------
   قائمة موانئ ومحافظات المنطقة
------------------------------------------------------------ */
$governorates = $pdo->prepare("SELECT id, name FROM governorates WHERE region_id = ? ORDER BY name");
$governorates->execute([$regionId]);
$governorates = $governorates->fetchAll();
$govIds = array_column($governorates, 'id');
$govPlaceholders = !empty($govIds) ? implode(',', array_fill(0, count($govIds), '?')) : '0';

$portsStmt = $pdo->prepare("SELECT id, name, governorate_id, is_active FROM ports WHERE governorate_id IN ($govPlaceholders)");
$portsStmt->execute($govIds ?: [0]);
$regionPorts = $portsStmt->fetchAll();
$portIds = array_column($regionPorts, 'id');
$portPlaceholders = !empty($portIds) ? implode(',', array_fill(0, count($portIds), '?')) : '0';
$pIds = $portIds ?: [0];

/* ------------------------------------------------------------
   المؤشرات الرئيسية
------------------------------------------------------------ */
$kpi = ['governorates' => count($governorates), 'ports' => count($regionPorts)];

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT ea.employee_id) FROM employee_assignments ea
     JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
     WHERE ea.port_id IN ($portPlaceholders) AND ea.assignment_date = CURDATE() AND a.status IN ('present','late')"
);
$stmt->execute($pIds);
$kpi['active_employees'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT ea.employee_id) FROM employee_assignments ea
     JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
     WHERE ea.port_id IN ($portPlaceholders) AND ea.assignment_date = CURDATE() AND a.status = 'absent'"
);
$stmt->execute($pIds);
$kpi['absent_employees'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM trips WHERE port_id IN ($portPlaceholders) AND DATE(actual_arrival) = CURDATE()"
);
$stmt->execute($pIds);
$kpi['trips_today'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(verified_weight),0) FROM trips
     WHERE port_id IN ($portPlaceholders) AND status IN ('approved','closed') AND DATE(actual_arrival) = CURDATE()"
);
$stmt->execute($pIds);
$kpi['approved_catch'] = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT td.trip_id) FROM trip_discrepancies td
     JOIN trips t ON t.id = td.trip_id
     WHERE t.port_id IN ($portPlaceholders) AND td.review_status != 'approved'"
);
$stmt->execute($pIds);
$kpi['diff_trips'] = (int)$stmt->fetchColumn();

// حساب حالة كل ميناء (مغطى/ضغط مرتفع/غير مغطى) لاستخدامها بأكثر من مكان
$portStatus = [];
foreach ($regionPorts as $p) {
    if (!$p['is_active']) { $portStatus[$p['id']] = 'inactive'; continue; }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM employee_assignments ea
         JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
         WHERE ea.port_id = ? AND ea.assignment_date = CURDATE() AND a.status IN ('present','late')"
    );
    $stmt->execute([$p['id']]);
    $present = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id = ? AND status IN ('arrived','waiting_employee','counting')");
    $stmt->execute([$p['id']]);
    $activeTrips = (int)$stmt->fetchColumn();

    if ($present === 0) $portStatus[$p['id']] = 'uncovered';
    elseif ($activeTrips > $present * 2) $portStatus[$p['id']] = 'high_load';
    else $portStatus[$p['id']] = 'covered';
}
$kpi['needs_support'] = count(array_filter($portStatus, fn($s) => in_array($s, ['uncovered', 'high_load'], true)));

/* ------------------------------------------------------------
   مقارنة المحافظات (إنتاج آخر 30 يومًا)
------------------------------------------------------------ */
$govComparison = [];
foreach ($governorates as $g) {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(t.verified_weight),0) AS total_kg, COUNT(t.id) AS trips_count
         FROM trips t JOIN ports p ON p.id = t.port_id
         WHERE p.governorate_id = ? AND t.status IN ('approved','closed')
           AND t.actual_arrival >= (NOW() - INTERVAL 30 DAY)"
    );
    $stmt->execute([$g['id']]);
    $r = $stmt->fetch();
    $govComparison[] = ['name' => $g['name'], 'total_kg' => $r['total_kg'], 'trips_count' => $r['trips_count']];
}
usort($govComparison, fn($a, $b) => $b['total_kg'] <=> $a['total_kg']);

/* ------------------------------------------------------------
   أداء كل محافظة اليوم (لقطة حالية)
------------------------------------------------------------ */
$govToday = [];
foreach ($governorates as $g) {
    $portsInGov = array_filter($regionPorts, fn($p) => (int)$p['governorate_id'] === (int)$g['id']);
    $govPortIds = array_column($portsInGov, 'id') ?: [0];
    $ph = implode(',', array_fill(0, count($govPortIds), '?'));

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id IN ($ph) AND DATE(actual_arrival) = CURDATE()");
    $stmt->execute($govPortIds);
    $tripsToday = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT AVG(td.diff_percent) FROM trip_discrepancies td JOIN trips t ON t.id = td.trip_id
         WHERE t.port_id IN ($ph) AND DATE(t.actual_arrival) = CURDATE()"
    );
    $stmt->execute($govPortIds);
    $avgDiff = (float)($stmt->fetchColumn() ?: 0);

    $covered = count(array_filter($portsInGov, fn($p) => ($portStatus[$p['id']] ?? '') === 'covered'));

    $govToday[] = [
        'name' => $g['name'], 'ports_count' => count($portsInGov),
        'trips_today' => $tripsToday, 'avg_diff' => $avgDiff, 'covered' => $covered,
    ];
}

/* ------------------------------------------------------------
   توزيع الموظفين على الموانئ (اليوم)
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT p.name AS port_name, COUNT(DISTINCT ea.employee_id) AS employees_count
     FROM employee_assignments ea JOIN ports p ON p.id = ea.port_id
     WHERE ea.port_id IN ($portPlaceholders) AND ea.assignment_date = CURDATE()
     GROUP BY p.id, p.name ORDER BY employees_count DESC"
);
$stmt->execute($pIds);
$staffDistribution = $stmt->fetchAll();

/* ------------------------------------------------------------
   أكثر الموانئ ازدحامًا
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT p.name AS port_name, COUNT(*) AS active_trips
     FROM trips t JOIN ports p ON p.id = t.port_id
     WHERE t.port_id IN ($portPlaceholders) AND t.status IN ('arrived','waiting_employee','counting')
     GROUP BY p.id, p.name ORDER BY active_trips DESC LIMIT 5"
);
$stmt->execute($pIds);
$busiestPorts = $stmt->fetchAll();

/* ------------------------------------------------------------
   الموظفون الأعلى أداءً (آخر 30 يوم، حسب عدد الرحلات المعتمدة)
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT u.full_name, COUNT(t.id) AS trips_count, COALESCE(SUM(t.verified_weight),0) AS total_weight
     FROM trips t
     JOIN employees e ON e.id = t.assigned_employee_id
     JOIN users u ON u.id = e.user_id
     WHERE t.port_id IN ($portPlaceholders) AND t.status IN ('approved','closed')
       AND t.actual_arrival >= (NOW() - INTERVAL 30 DAY)
     GROUP BY e.id, u.full_name ORDER BY trips_count DESC LIMIT 5"
);
$stmt->execute($pIds);
$topEmployees = $stmt->fetchAll();

/* ------------------------------------------------------------
   تنبيهات نقص التغطية
------------------------------------------------------------ */
$coverageAlerts = [];
foreach ($regionPorts as $p) {
    $status = $portStatus[$p['id']] ?? 'covered';
    if ($status === 'uncovered') {
        $coverageAlerts[] = ['port' => $p['name'], 'message' => 'بدون أي موظف حاضر اليوم', 'severity' => 'critical'];
    } elseif ($status === 'high_load') {
        $coverageAlerts[] = ['port' => $p['name'], 'message' => 'عدد القوارب النشطة يفوق طاقة الموظفين الحاليين', 'severity' => 'warning'];
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if ($role !== 'region_manager' && count($regionsList) > 1): ?>
<form method="get" class="panel" style="display:flex; gap:12px; align-items:center; padding:14px 18px;">
    <label style="font-weight:700; font-size:13.5px;">المنطقة:</label>
    <select name="region_id" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid var(--line);">
        <?php foreach ($regionsList as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $r['id'] == $regionId ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">المحافظات التابعة</span><span class="stat-value"><?= numberAr($kpi['governorates']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموانئ التابعة</span><span class="stat-value"><?= numberAr($kpi['ports']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون النشطون</span><span class="stat-value"><?= numberAr($kpi['active_employees']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">الموظفون الغائبون</span><span class="stat-value"><?= numberAr($kpi['absent_employees']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الرحلات اليوم</span><span class="stat-value"><?= numberAr($kpi['trips_today']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">المصيد المعتمد اليوم (كجم)</span><span class="stat-value"><?= numberAr($kpi['approved_catch']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">الرحلات ذات الفروقات</span><span class="stat-value"><?= numberAr($kpi['diff_trips']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">الموانئ المحتاجة للدعم</span><span class="stat-value"><?= numberAr($kpi['needs_support']) ?></span></div>
</div>

<div class="grid-2">
    <div class="panel">
        <h3>مقارنة المحافظات (إنتاج آخر 30 يومًا)</h3>
        <?php if (empty($govComparison)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>المحافظة</th><th>عدد الرحلات</th><th>الإنتاج (كجم)</th></tr></thead>
            <tbody>
            <?php foreach ($govComparison as $g): ?>
                <tr><td><?= e($g['name']) ?></td><td class="num"><?= numberAr($g['trips_count']) ?></td><td class="num"><?= numberAr($g['total_kg']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>أداء كل محافظة اليوم</h3>
        <?php if (empty($govToday)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>المحافظة</th><th>الموانئ</th><th>رحلات اليوم</th><th>متوسط الفرق</th><th>موانئ مغطاة</th></tr></thead>
            <tbody>
            <?php foreach ($govToday as $g): ?>
                <tr>
                    <td><?= e($g['name']) ?></td>
                    <td class="num"><?= numberAr($g['ports_count']) ?></td>
                    <td class="num"><?= numberAr($g['trips_today']) ?></td>
                    <td class="num"><?= numberAr($g['avg_diff'],1) ?>%</td>
                    <td class="num"><?= numberAr($g['covered']) ?>/<?= numberAr($g['ports_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <h3>توزيع الموظفين على الموانئ (اليوم)</h3>
        <?php if (empty($staffDistribution)): ?><p class="panel-hint">لا توجد تكليفات اليوم.</p><?php else: ?>
        <table>
            <thead><tr><th>الميناء</th><th>عدد الموظفين</th></tr></thead>
            <tbody>
            <?php foreach ($staffDistribution as $s): ?>
                <tr><td><?= e($s['port_name']) ?></td><td class="num"><?= numberAr($s['employees_count']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>أكثر الموانئ ازدحامًا الآن</h3>
        <?php if (empty($busiestPorts)): ?><p class="panel-hint">لا يوجد ازدحام حاليًا. ✅</p><?php else: ?>
        <table>
            <thead><tr><th>الميناء</th><th>قوارب نشطة</th></tr></thead>
            <tbody>
            <?php foreach ($busiestPorts as $b): ?>
                <tr><td><?= e($b['port_name']) ?></td><td class="num"><?= numberAr($b['active_trips']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <h3>الموظفون الأعلى أداءً (آخر 30 يوم)</h3>
        <?php if (empty($topEmployees)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>الموظف</th><th>عدد الرحلات</th><th>الكمية (كجم)</th></tr></thead>
            <tbody>
            <?php foreach ($topEmployees as $e): ?>
                <tr><td><?= e($e['full_name']) ?></td><td class="num"><?= numberAr($e['trips_count']) ?></td><td class="num"><?= numberAr($e['total_weight']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>تنبيهات نقص التغطية</h3>
        <?php if (empty($coverageAlerts)): ?>
            <p class="panel-hint">لا توجد تنبيهات تغطية حاليًا. ✅</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الميناء</th><th>التنبيه</th><th>الخطورة</th></tr></thead>
            <tbody>
            <?php foreach ($coverageAlerts as $a): ?>
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
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
