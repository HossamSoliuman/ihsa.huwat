<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'region_manager']);
$pageTitle   = 'التغطية الجغرافية';
$activeRoute = 'coverage.php';

$pdo = db();
$role = $currentUserData['role_code'];

$regionFilter = isset($_GET['region_id']) ? (int)$_GET['region_id'] : 0;
if ($role === 'region_manager') {
    $regionFilter = (int)$currentUserData['region_id'];
}
$portDetailId = isset($_GET['port_detail']) ? (int)$_GET['port_detail'] : 0;

$redirectUrl = BASE_URL . '/dashboard/coverage.php' . ($regionFilter ? '?region_id=' . $regionFilter : '');

/* ------------------------------------------------------------
   إجراء: تكليف موظف من صفحة التفاصيل
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_from_coverage') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $portId     = (int)$_POST['port_id'];
    $employeeId = (int)$_POST['employee_id'];
    $shiftId    = (int)$_POST['shift_id'];
    $detailRedirect = $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . 'port_detail=' . $portId;
    try {
        $pdo->prepare(
            "INSERT INTO employee_assignments (employee_id, port_id, shift_id, assignment_date, is_temporary)
             VALUES (?, ?, ?, CURDATE(), 1)"
        )->execute([$employeeId, $portId, $shiftId]);
        redirectWithMessage($detailRedirect, 'success', 'تم تكليف الموظف بنجاح.');
    } catch (Throwable $e) {
        error_log('Coverage assign error: ' . $e->getMessage());
        redirectWithMessage($detailRedirect, 'error', 'تعذر التكليف (قد يكون الموظف مسندًا بمكان آخر اليوم).');
    }
}

$regionsList = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();

/* ------------------------------------------------------------
   جلب كل الموانئ ضمن النطاق مع بيانات التغطية والضغط
------------------------------------------------------------ */
$sql = "SELECT p.id, p.name, p.is_active, g.name AS gov_name, r.id AS region_id, r.name AS region_name
        FROM ports p
        JOIN governorates g ON g.id = p.governorate_id
        JOIN regions r ON r.id = g.region_id";
$params = [];
if ($regionFilter > 0) {
    $sql .= " WHERE r.id = ?";
    $params[] = $regionFilter;
}
$sql .= " ORDER BY r.name, g.name, p.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ports = $stmt->fetchAll();

$kpi = [
    'regions' => count($regionsList),
    'governorates' => (int)$pdo->query("SELECT COUNT(*) FROM governorates")->fetchColumn(),
    'ports' => count($ports),
    'covered' => 0, 'uncovered' => 0, 'high_load' => 0,
    'available_employees' => 0,
    'temp_assignments' => (int)$pdo->query("SELECT COUNT(*) FROM employee_assignments WHERE assignment_date = CURDATE() AND is_temporary = 1")->fetchColumn(),
];

foreach ($ports as &$p) {
    if (!$p['is_active']) {
        $p['coverage_status'] = 'inactive';
        continue;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM employee_assignments ea
         JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
         WHERE ea.port_id = ? AND ea.assignment_date = CURDATE() AND a.status IN ('present','late')"
    );
    $stmt->execute([$p['id']]);
    $presentCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM trips WHERE port_id = ? AND status IN ('arrived','waiting_employee','counting')"
    );
    $stmt->execute([$p['id']]);
    $activeTripsCount = (int)$stmt->fetchColumn();

    $p['present_count'] = $presentCount;
    $p['active_trips'] = $activeTripsCount;

    if ($presentCount === 0) {
        $p['coverage_status'] = 'uncovered';
        $kpi['uncovered']++;
    } elseif ($activeTripsCount > $presentCount * 2) {
        $p['coverage_status'] = 'high_load';
        $kpi['high_load']++;
        $kpi['covered']++;
    } else {
        $p['coverage_status'] = 'covered';
        $kpi['covered']++;
    }
}
unset($p);

$kpi['available_employees'] = (int)$pdo->query(
    "SELECT COUNT(DISTINCT ea.employee_id) FROM employee_assignments ea
     JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
     WHERE ea.assignment_date = CURDATE() AND a.status IN ('present','late')
       AND NOT EXISTS (SELECT 1 FROM trips t WHERE t.assigned_employee_id = ea.employee_id AND t.status IN ('waiting_employee','counting'))"
)->fetchColumn();

/* ------------------------------------------------------------
   تفاصيل الميناء المختار
------------------------------------------------------------ */
$portDetail = null;
if ($portDetailId) {
    foreach ($ports as $p) {
        if ((int)$p['id'] === $portDetailId) { $portDetail = $p; break; }
    }
    if ($portDetail) {
        $stmt = $pdo->prepare(
            "SELECT t.trip_code, t.expected_arrival, b.name AS boat_name
             FROM trips t JOIN boats b ON b.id = t.boat_id
             WHERE t.port_id = ? AND t.status = 'expected' ORDER BY t.expected_arrival"
        );
        $stmt->execute([$portDetailId]);
        $portDetail['expected_trips'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT u.full_name, s.name AS shift_name, a.status AS attendance_status
             FROM employee_assignments ea
             JOIN employees e ON e.id = ea.employee_id
             JOIN users u ON u.id = e.user_id
             JOIN shifts s ON s.id = ea.shift_id
             LEFT JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
             WHERE ea.port_id = ? AND ea.assignment_date = CURDATE()"
        );
        $stmt->execute([$portDetailId]);
        $portDetail['staff'] = $stmt->fetchAll();

        $availableStaff = array_filter($portDetail['staff'], fn($s) => ($s['attendance_status'] ?? null) === 'present');
        $portDetail['available_staff_count'] = count($availableStaff);

        // احتياج مقترح
        if ($portDetail['coverage_status'] === 'uncovered') {
            $portDetail['suggested_need'] = 'يحتاج موظف واحد على الأقل فورًا لتغطية هذا الميناء اليوم.';
        } elseif ($portDetail['coverage_status'] === 'high_load') {
            $portDetail['suggested_need'] = 'يُقترح إضافة موظف إحصاء إضافي لتخفيف الضغط الحالي.';
        } else {
            $portDetail['suggested_need'] = 'التغطية الحالية كافية.';
        }
    }
}

$allShifts = $pdo->query("SELECT * FROM shifts ORDER BY start_time")->fetchAll();
$availableEmployeesGlobal = $pdo->query(
    "SELECT e.id, u.full_name FROM employees e JOIN users u ON u.id = e.user_id
     WHERE e.status = 'active'
       AND e.id NOT IN (SELECT employee_id FROM employee_assignments WHERE assignment_date = CURDATE())
     ORDER BY u.full_name"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (count($regionsList) > 1 && $role !== 'region_manager'): ?>
<form method="get" class="panel" style="display:flex; gap:12px; align-items:center; padding:14px 18px;">
    <label style="font-weight:700; font-size:13.5px;">المنطقة:</label>
    <select name="region_id" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid var(--line);">
        <option value="0">كل المناطق</option>
        <?php foreach ($regionsList as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $r['id'] == $regionFilter ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">عدد المناطق</span><span class="stat-value"><?= numberAr($kpi['regions']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">عدد المحافظات</span><span class="stat-value"><?= numberAr($kpi['governorates']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">عدد الموانئ</span><span class="stat-value"><?= numberAr($kpi['ports']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموانئ المغطاة</span><span class="stat-value"><?= numberAr($kpi['covered']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">الموانئ غير المغطاة</span><span class="stat-value"><?= numberAr($kpi['uncovered']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">موانئ ذات ضغط مرتفع</span><span class="stat-value"><?= numberAr($kpi['high_load']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون المتاحون</span><span class="stat-value"><?= numberAr($kpi['available_employees']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">التكليفات المؤقتة اليوم</span><span class="stat-value"><?= numberAr($kpi['temp_assignments']) ?></span></div>
</div>

<!-- خريطة حالة الموانئ (شبكة بطاقات ملوّنة بدل خريطة جغرافية تفاعلية) -->
<div class="panel">
    <h3>خريطة حالة الموانئ</h3>
    <div style="display:flex; gap:16px; margin-bottom:16px; font-size:12.5px; flex-wrap:wrap;">
        <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--success); margin-inline-end:5px;"></span> تغطية كافية</span>
        <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--warning); margin-inline-end:5px;"></span> ضغط مرتفع</span>
        <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--danger); margin-inline-end:5px;"></span> نقص موظفين</span>
        <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#9AA3A8; margin-inline-end:5px;"></span> غير نشط</span>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:12px;">
        <?php foreach ($ports as $p):
            $color = match($p['coverage_status']) {
                'covered' => 'var(--success)', 'high_load' => 'var(--warning)',
                'uncovered' => 'var(--danger)', default => '#9AA3A8',
            };
        ?>
        <a href="?<?= $regionFilter ? 'region_id=' . $regionFilter . '&' : '' ?>port_detail=<?= (int)$p['id'] ?>"
           style="display:block; border:1px solid var(--line); border-top:4px solid <?= $color ?>; border-radius:10px; padding:12px 14px; background:#fff; <?= $portDetailId == $p['id'] ? 'box-shadow:0 0 0 2px ' . $color . ';' : '' ?>">
            <strong style="font-size:13.5px;"><?= e($p['name']) ?></strong><br>
            <span style="font-size:11.5px; color:var(--muted);"><?= e($p['gov_name']) ?> — <?= e($p['region_name']) ?></span>
            <?php if ($p['is_active']): ?>
                <div style="font-size:11px; margin-top:6px;">
                    موظفون حاضرون: <strong><?= numberAr($p['present_count']) ?></strong> · قوارب نشطة: <strong><?= numberAr($p['active_trips']) ?></strong>
                </div>
            <?php else: ?>
                <div style="font-size:11px; margin-top:6px; color:var(--muted);">ميناء غير نشط</div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- تفاصيل الميناء المختار -->
<?php if ($portDetail): ?>
<div class="panel">
    <h3>تفاصيل ميناء: <?= e($portDetail['name']) ?></h3>
    <p class="panel-hint"><?= e($portDetail['suggested_need']) ?></p>

    <div class="grid-2">
        <div>
            <h4 style="font-size:13.5px; margin-bottom:8px;">القوارب المتوقعة</h4>
            <?php if (empty($portDetail['expected_trips'])): ?>
                <p class="panel-hint">لا توجد قوارب متوقعة حاليًا.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>الرحلة</th><th>القارب</th><th>الوصول المتوقع</th></tr></thead>
                <tbody>
                <?php foreach ($portDetail['expected_trips'] as $t): ?>
                    <tr><td><?= e($t['trip_code']) ?></td><td><?= e($t['boat_name']) ?></td><td><?= e($t['expected_arrival']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div>
            <h4 style="font-size:13.5px; margin-bottom:8px;">
                عدد الموظفين اليوم: <?= numberAr(count($portDetail['staff'])) ?> —
                المتاحون: <?= numberAr($portDetail['available_staff_count']) ?>
            </h4>
            <?php if (empty($portDetail['staff'])): ?>
                <p class="panel-hint">لا يوجد موظفون مسندون اليوم.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>الموظف</th><th>المناوبة</th><th>الحالة</th></tr></thead>
                <tbody>
                <?php foreach ($portDetail['staff'] as $s): ?>
                    <tr>
                        <td><?= e($s['full_name']) ?></td>
                        <td><?= e($s['shift_name']) ?></td>
                        <td><?= e($s['attendance_status'] ?? 'لم يبدأ') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <h4 style="font-size:13.5px; margin:16px 0 8px;">تكليف موظف لهذا الميناء</h4>
    <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="assign_from_coverage">
        <input type="hidden" name="port_id" value="<?= (int)$portDetail['id'] ?>">
        <select name="employee_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
            <option value="">اختر موظف متاح (بدون تكليف اليوم)</option>
            <?php foreach ($availableEmployeesGlobal as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= e($emp['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="shift_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
            <?php foreach ($allShifts as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">تكليف الآن</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
