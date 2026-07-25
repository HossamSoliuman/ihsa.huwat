<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'region_manager', 'gov_supervisor']);
$pageTitle   = 'التقارير والتحليلات';
$activeRoute = 'reports.php';

$pdo = db();
$role = $currentUserData['role_code'];

/* ------------------------------------------------------------
   نطاق الموانئ المسموح بها حسب الدور (تُستخدم كفلتر أساسي لكل تقرير له علاقة بالموانئ)
------------------------------------------------------------ */
if ($role === 'gov_supervisor') {
    $stmt = $pdo->prepare("SELECT id FROM ports WHERE governorate_id = ?");
    $stmt->execute([$currentUserData['governorate_id']]);
    $scopePortIds = array_column($stmt->fetchAll(), 'id');
} elseif ($role === 'region_manager') {
    $stmt = $pdo->prepare(
        "SELECT p.id FROM ports p JOIN governorates g ON g.id = p.governorate_id WHERE g.region_id = ?"
    );
    $stmt->execute([$currentUserData['region_id']]);
    $scopePortIds = array_column($stmt->fetchAll(), 'id');
} else {
    $scopePortIds = array_column($pdo->query("SELECT id FROM ports")->fetchAll(), 'id');
}
$scopePortIds = $scopePortIds ?: [0];
$scopePh = implode(',', array_fill(0, count($scopePortIds), '?'));

/* ------------------------------------------------------------
   الفلاتر العامة
------------------------------------------------------------ */
$reportType = $_GET['report_type'] ?? 'trips';
$dateFrom   = $_GET['date_from'] ?? date('Y-m-01');
$dateTo     = $_GET['date_to'] ?? date('Y-m-d');
$regionId   = (int)($_GET['region_id'] ?? 0);
$govId      = (int)($_GET['gov_id'] ?? 0);
$portId     = (int)($_GET['port_id'] ?? 0);
$boatId     = (int)($_GET['boat_id'] ?? 0);
$captainId  = (int)($_GET['captain_id'] ?? 0);
$employeeId = (int)($_GET['employee_id'] ?? 0);
$speciesId  = (int)($_GET['species_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$diffMin    = $_GET['diff_min'] ?? '';
$diffMax    = $_GET['diff_max'] ?? '';

$reportTypes = [
    'trips'         => 'تقرير الرحلات',
    'catch'         => 'تقرير المصيد المعتمد',
    'discrepancies' => 'تقرير فروقات المصيد',
    'employees'     => 'تقرير أداء الموظفين',
    'ports'         => 'تقرير أداء الموانئ',
    'attendance'    => 'تقرير الحضور والانصراف',
    'shifts'        => 'تقرير المناوبات',
    'leaves'        => 'تقرير الإجازات والتكليفات',
    'payroll'       => 'تقرير الرواتب',
    'coverage'      => 'تقرير التغطية الجغرافية',
    'species'       => 'تقرير أنواع الأسماك',
    'boats'         => 'تقرير الكباتن والقوارب',
];

$statusOptions = [
    '' => 'كل الحالات', 'expected' => 'متوقعة', 'arrived' => 'واصلة',
    'waiting_employee' => 'بانتظار موظف', 'counting' => 'تحت الإحصاء',
    'pending_review' => 'بانتظار مراجعة', 'approved' => 'معتمدة', 'closed' => 'مغلقة',
];

/* ------------------------------------------------------------
   بيانات القوائم المنسدلة
------------------------------------------------------------ */
$regionsList = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();
$govsList    = $pdo->query("SELECT id, name FROM governorates ORDER BY name")->fetchAll();
$portsList   = $pdo->query("SELECT id, name FROM ports ORDER BY name")->fetchAll();
$boatsList   = $pdo->query("SELECT id, name FROM boats ORDER BY name")->fetchAll();
$captainsList = $pdo->query("SELECT id, full_name FROM captains ORDER BY full_name")->fetchAll();
$employeesList = $pdo->query("SELECT e.id, u.full_name FROM employees e JOIN users u ON u.id = e.user_id ORDER BY u.full_name")->fetchAll();
$speciesList = $pdo->query("SELECT id, name_ar FROM fish_species ORDER BY name_ar")->fetchAll();

/* ------------------------------------------------------------
   بناء شرط نطاق الموانئ المشترك + فلاتر الموقع الإضافية
------------------------------------------------------------ */
function buildPortScopeClause(string $portColumnRef, array $scopePortIds, int $regionId, int $govId, int $portId, PDO $pdo): array {
    $conditions = ["$portColumnRef IN (" . implode(',', array_fill(0, count($scopePortIds), '?')) . ")"];
    $params = $scopePortIds;

    if ($portId > 0) {
        $conditions[] = "$portColumnRef = ?";
        $params[] = $portId;
    } elseif ($govId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM ports WHERE governorate_id = ?");
        $stmt->execute([$govId]);
        $ids = array_column($stmt->fetchAll(), 'id') ?: [0];
        $conditions[] = "$portColumnRef IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $params = array_merge($params, $ids);
    } elseif ($regionId > 0) {
        $stmt = $pdo->prepare(
            "SELECT p.id FROM ports p JOIN governorates g ON g.id = p.governorate_id WHERE g.region_id = ?"
        );
        $stmt->execute([$regionId]);
        $ids = array_column($stmt->fetchAll(), 'id') ?: [0];
        $conditions[] = "$portColumnRef IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $params = array_merge($params, $ids);
    }
    return [implode(' AND ', $conditions), $params];
}

[$portScopeClause, $portScopeParams] = buildPortScopeClause('t.port_id', $scopePortIds, $regionId, $govId, $portId, $pdo);

$rows = [];
$columns = [];

/* ============================================================
   تنفيذ الاستعلام حسب نوع التقرير المختار
============================================================ */
switch ($reportType) {

    case 'trips':
        $columns = ['الرحلة','الميناء','القارب','الكابتن','الحالة','الوصول الفعلي','الوزن المبلغ','الوزن الفعلي'];
        $sql = "SELECT t.trip_code, p.name AS port_name, b.name AS boat_name, c.full_name AS captain_name,
                       t.status, t.actual_arrival, t.captain_reported_weight, t.verified_weight
                FROM trips t
                JOIN ports p ON p.id = t.port_id
                JOIN boats b ON b.id = t.boat_id
                JOIN captains c ON c.id = t.captain_id
                WHERE $portScopeClause
                  AND DATE(COALESCE(t.actual_arrival, t.expected_arrival)) BETWEEN ? AND ?";
        $params = $portScopeParams;
        $params[] = $dateFrom; $params[] = $dateTo;
        if ($boatId) { $sql .= " AND t.boat_id = ?"; $params[] = $boatId; }
        if ($captainId) { $sql .= " AND t.captain_id = ?"; $params[] = $captainId; }
        if ($employeeId) { $sql .= " AND t.assigned_employee_id = ?"; $params[] = $employeeId; }
        if ($statusFilter) { $sql .= " AND t.status = ?"; $params[] = $statusFilter; }
        $sql .= " ORDER BY t.actual_arrival DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['trip_code'], $r['port_name'], $r['boat_name'], $r['captain_name'], $statusOptions[$r['status']] ?? $r['status'], $r['actual_arrival'] ?: '—', numberAr($r['captain_reported_weight']), numberAr($r['verified_weight'])];
        }
        break;

    case 'catch':
        $columns = ['الرحلة','الميناء','تاريخ الاعتماد','الكمية المعتمدة (كجم)'];
        $sql = "SELECT t.trip_code, p.name AS port_name, t.approved_at, t.verified_weight
                FROM trips t JOIN ports p ON p.id = t.port_id
                WHERE $portScopeClause AND t.status IN ('approved','closed')
                  AND DATE(t.actual_arrival) BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($speciesId) {
            $sql .= " AND t.id IN (SELECT trip_id FROM catch_details WHERE species_id = ?)";
            $params[] = $speciesId;
        }
        $sql .= " ORDER BY t.approved_at DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['trip_code'], $r['port_name'], $r['approved_at'] ?: '—', numberAr($r['verified_weight'])];
        }
        break;

    case 'discrepancies':
        $columns = ['الرحلة','الميناء','الفرق (كجم)','النسبة%','التصنيف','حالة المراجعة'];
        $sql = "SELECT t.trip_code, p.name AS port_name, td.diff_kg, td.diff_percent, td.severity, td.review_status
                FROM trip_discrepancies td JOIN trips t ON t.id = td.trip_id JOIN ports p ON p.id = t.port_id
                WHERE $portScopeClause AND DATE(t.actual_arrival) BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($diffMin !== '') { $sql .= " AND ABS(td.diff_percent) >= ?"; $params[] = (float)$diffMin; }
        if ($diffMax !== '') { $sql .= " AND ABS(td.diff_percent) <= ?"; $params[] = (float)$diffMax; }
        $sql .= " ORDER BY td.diff_percent DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['trip_code'], $r['port_name'], numberAr($r['diff_kg']), numberAr($r['diff_percent'],1), severityLabel($r['severity']), $r['review_status']];
        }
        break;

    case 'employees':
        $columns = ['الموظف','عدد الرحلات','الكمية (كجم)','متوسط الفرق%'];
        $sql = "SELECT u.full_name, COUNT(t.id) AS trips_count, COALESCE(SUM(t.verified_weight),0) AS total_weight,
                       AVG(td.diff_percent) AS avg_diff
                FROM trips t
                JOIN employees e ON e.id = t.assigned_employee_id
                JOIN users u ON u.id = e.user_id
                LEFT JOIN trip_discrepancies td ON td.trip_id = t.id
                WHERE $portScopeClause AND t.status IN ('approved','closed')
                  AND DATE(t.actual_arrival) BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($employeeId) { $sql .= " AND e.id = ?"; $params[] = $employeeId; }
        $sql .= " GROUP BY e.id, u.full_name ORDER BY trips_count DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['full_name'], numberAr($r['trips_count']), numberAr($r['total_weight']), numberAr($r['avg_diff'],1)];
        }
        break;

    case 'ports':
        $columns = ['الميناء','عدد الرحلات','الكمية المعتمدة (كجم)','متوسط الفرق%'];
        $sql = "SELECT p.name, COUNT(t.id) AS trips_count, COALESCE(SUM(t.verified_weight),0) AS total_weight,
                       AVG(td.diff_percent) AS avg_diff
                FROM trips t JOIN ports p ON p.id = t.port_id
                LEFT JOIN trip_discrepancies td ON td.trip_id = t.id
                WHERE $portScopeClause AND t.status IN ('approved','closed')
                  AND DATE(t.actual_arrival) BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        $sql .= " GROUP BY p.id, p.name ORDER BY trips_count DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['name'], numberAr($r['trips_count']), numberAr($r['total_weight']), numberAr($r['avg_diff'],1)];
        }
        break;

    case 'attendance':
        $columns = ['الموظف','الميناء','التاريخ','المناوبة','الحالة','الحضور','الانصراف'];
        $sql = "SELECT u.full_name, p.name AS port_name, a.attendance_date, s.name AS shift_name,
                       a.status, a.check_in, a.check_out
                FROM attendance a
                JOIN employees e ON e.id = a.employee_id
                JOIN users u ON u.id = e.user_id
                JOIN shifts s ON s.id = a.shift_id
                JOIN employee_assignments ea ON ea.employee_id = a.employee_id AND ea.assignment_date = a.attendance_date AND ea.shift_id = a.shift_id
                JOIN ports p ON p.id = ea.port_id
                WHERE " . str_replace('t.port_id', 'p.id', $portScopeClause) . "
                  AND a.attendance_date BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($employeeId) { $sql .= " AND e.id = ?"; $params[] = $employeeId; }
        $sql .= " ORDER BY a.attendance_date DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['full_name'], $r['port_name'], $r['attendance_date'], $r['shift_name'], $r['status'], $r['check_in'] ?: '—', $r['check_out'] ?: '—'];
        }
        break;

    case 'shifts':
        $columns = ['الموظف','الميناء','التاريخ','المناوبة','بديل؟'];
        $sql = "SELECT u.full_name, p.name AS port_name, ea.assignment_date, s.name AS shift_name, ea.is_temporary
                FROM employee_assignments ea
                JOIN employees e ON e.id = ea.employee_id
                JOIN users u ON u.id = e.user_id
                JOIN ports p ON p.id = ea.port_id
                JOIN shifts s ON s.id = ea.shift_id
                WHERE " . str_replace('t.port_id', 'p.id', $portScopeClause) . "
                  AND ea.assignment_date BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($employeeId) { $sql .= " AND e.id = ?"; $params[] = $employeeId; }
        $sql .= " ORDER BY ea.assignment_date DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['full_name'], $r['port_name'], $r['assignment_date'], $r['shift_name'], $r['is_temporary'] ? 'نعم' : 'لا'];
        }
        break;

    case 'leaves':
        $columns = ['الموظف','من','إلى','الحالة','السبب'];
        $sql = "SELECT u.full_name, l.start_date, l.end_date, l.status, l.reason
                FROM leaves l JOIN employees e ON e.id = l.employee_id JOIN users u ON u.id = e.user_id
                WHERE l.start_date BETWEEN ? AND ? OR l.end_date BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo];
        if ($employeeId) { $sql .= " AND e.id = ?"; $params[] = $employeeId; }
        $sql .= " ORDER BY l.start_date DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['full_name'], $r['start_date'], $r['end_date'], $r['status'], $r['reason'] ?: '—'];
        }
        break;

    case 'payroll':
        $columns = ['الموظف','الشهر/السنة','الأساسي','الصافي','حالة الصرف'];
        $sql = "SELECT u.full_name, pr.period_month, pr.period_year, pr.base_salary, pr.net_salary, pr.paid_status
                FROM payroll pr JOIN employees e ON e.id = pr.employee_id JOIN users u ON u.id = e.user_id
                WHERE STR_TO_DATE(CONCAT(pr.period_year,'-',pr.period_month,'-01'), '%Y-%m-%d') BETWEEN
                      DATE_FORMAT(?, '%Y-%m-01') AND ?";
        $params = [$dateFrom, $dateTo];
        if ($employeeId) { $sql .= " AND e.id = ?"; $params[] = $employeeId; }
        $sql .= " ORDER BY pr.period_year DESC, pr.period_month DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['full_name'], $r['period_month'] . '/' . $r['period_year'], numberAr($r['base_salary']), numberAr($r['net_salary']), $r['paid_status'] === 'paid' ? 'مصروف' : 'معلّق'];
        }
        break;

    case 'coverage':
        $columns = ['الميناء','المحافظة','نشط؟','موظفون حاضرون اليوم','قوارب نشطة الآن','الحالة'];
        $sql = "SELECT p.id, p.name, p.is_active, g.name AS gov_name
                FROM ports p JOIN governorates g ON g.id = p.governorate_id
                WHERE $portScopeClause";
        // إعادة استخدام buildPortScopeClause لكنه مبني على t.port_id، نستبدله هنا لأنه لا يوجد t
        $sql = str_replace('t.port_id', 'p.id', $sql);
        $stmt = $pdo->prepare($sql); $stmt->execute($portScopeParams);
        foreach ($stmt->fetchAll() as $p) {
            $stmt2 = $pdo->prepare(
                "SELECT COUNT(*) FROM employee_assignments ea
                 JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
                 WHERE ea.port_id = ? AND ea.assignment_date = CURDATE() AND a.status IN ('present','late')"
            );
            $stmt2->execute([$p['id']]);
            $present = (int)$stmt2->fetchColumn();

            $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE port_id = ? AND status IN ('arrived','waiting_employee','counting')");
            $stmt3->execute([$p['id']]);
            $active = (int)$stmt3->fetchColumn();

            $status = !$p['is_active'] ? 'غير نشط' : ($present === 0 ? 'غير مغطى' : ($active > $present * 2 ? 'ضغط مرتفع' : 'مغطى'));
            $rows[] = [$p['name'], $p['gov_name'], $p['is_active'] ? 'نعم' : 'لا', numberAr($present), numberAr($active), $status];
        }
        break;

    case 'species':
        $columns = ['النوع','عدد الرحلات','إجمالي الكمية المعتمدة (كجم)'];
        $sql = "SELECT fs.name_ar, COUNT(DISTINCT cd.trip_id) AS trips_count, SUM(cd.verified_kg) AS total_kg
                FROM catch_details cd
                JOIN fish_species fs ON fs.id = cd.species_id
                JOIN trips t ON t.id = cd.trip_id
                WHERE $portScopeClause AND t.status IN ('approved','closed')
                  AND DATE(t.actual_arrival) BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($speciesId) { $sql .= " AND fs.id = ?"; $params[] = $speciesId; }
        $sql .= " GROUP BY fs.id, fs.name_ar ORDER BY total_kg DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['name_ar'], numberAr($r['trips_count']), numberAr($r['total_kg'])];
        }
        break;

    case 'boats':
        $columns = ['القارب','الكابتن','عدد الرحلات','إجمالي الكمية (كجم)','متوسط الفرق%'];
        $sql = "SELECT b.name AS boat_name, c.full_name AS captain_name,
                       COUNT(t.id) AS trips_count, COALESCE(SUM(t.verified_weight),0) AS total_weight,
                       AVG(td.diff_percent) AS avg_diff
                FROM trips t
                JOIN boats b ON b.id = t.boat_id
                JOIN captains c ON c.id = t.captain_id
                LEFT JOIN trip_discrepancies td ON td.trip_id = t.id
                WHERE $portScopeClause AND t.status IN ('approved','closed')
                  AND DATE(t.actual_arrival) BETWEEN ? AND ?";
        $params = $portScopeParams; $params[] = $dateFrom; $params[] = $dateTo;
        if ($boatId) { $sql .= " AND b.id = ?"; $params[] = $boatId; }
        if ($captainId) { $sql .= " AND c.id = ?"; $params[] = $captainId; }
        $sql .= " GROUP BY b.id, b.name, c.id, c.full_name ORDER BY trips_count DESC LIMIT 500";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $rows[] = [$r['boat_name'], $r['captain_name'], numberAr($r['trips_count']), numberAr($r['total_weight']), numberAr($r['avg_diff'],1)];
        }
        break;
}

require __DIR__ . '/../../includes/header.php';
?>

<!-- اختيار نوع التقرير -->
<div class="panel" style="padding:16px 20px;">
    <form method="get" style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php foreach ($reportTypes as $key => $label): ?>
            <button type="submit" name="report_type" value="<?= e($key) ?>"
                    class="btn <?= $key === $reportType ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </form>
</div>

<!-- الفلاتر المشتركة -->
<form method="get" class="panel" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; padding:16px 20px;">
    <input type="hidden" name="report_type" value="<?= e($reportType) ?>">

    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">من تاريخ</label>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
    </div>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">إلى تاريخ</label>
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
    </div>

    <?php if (in_array($reportType, ['trips','catch','discrepancies','employees','ports','attendance','shifts','species','boats','coverage'], true)): ?>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">المنطقة</label>
        <select name="region_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($regionsList as $r): ?><option value="<?= (int)$r['id'] ?>" <?= $r['id']==$regionId?'selected':'' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">المحافظة</label>
        <select name="gov_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($govsList as $g): ?><option value="<?= (int)$g['id'] ?>" <?= $g['id']==$govId?'selected':'' ?>><?= e($g['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">الميناء</label>
        <select name="port_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($portsList as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $p['id']==$portId?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (in_array($reportType, ['trips','boats'], true)): ?>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">القارب</label>
        <select name="boat_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($boatsList as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $b['id']==$boatId?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">الكابتن</label>
        <select name="captain_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($captainsList as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $c['id']==$captainId?'selected':'' ?>><?= e($c['full_name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (in_array($reportType, ['trips','employees','attendance','shifts','leaves','payroll'], true)): ?>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">موظف الإحصاء</label>
        <select name="employee_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($employeesList as $emp): ?><option value="<?= (int)$emp['id'] ?>" <?= $emp['id']==$employeeId?'selected':'' ?>><?= e($emp['full_name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (in_array($reportType, ['catch','species'], true)): ?>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">نوع السمك</label>
        <select name="species_id" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <option value="0">الكل</option>
            <?php foreach ($speciesList as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $s['id']==$speciesId?'selected':'' ?>><?= e($s['name_ar']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if ($reportType === 'trips'): ?>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">حالة الرحلة</label>
        <select name="status" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
            <?php foreach ($statusOptions as $val => $label): ?><option value="<?= e($val) ?>" <?= $val===$statusFilter?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if ($reportType === 'discrepancies'): ?>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">نسبة الفرق من%</label>
        <input type="number" step="0.1" name="diff_min" value="<?= e($diffMin) ?>" style="width:80px; padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
    </div>
    <div>
        <label style="display:block; font-size:11.5px; font-weight:700; margin-bottom:4px;">إلى%</label>
        <input type="number" step="0.1" name="diff_max" value="<?= e($diffMax) ?>" style="width:80px; padding:6px 8px; border-radius:8px; border:1px solid var(--line); font-size:12.5px;">
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
</form>

<!-- نتائج التقرير -->
<div class="panel">
    <h3><?= e($reportTypes[$reportType] ?? 'التقرير') ?></h3>
    <p class="panel-hint">عدد النتائج: <?= numberAr(count($rows)) ?> (الحد الأقصى المعروض 500 صف)</p>

    <?php if (empty($rows)): ?>
        <p class="panel-hint">لا توجد بيانات مطابقة لهذه الفلاتر.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead><tr><?php foreach ($columns as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><?php foreach ($row as $cell): ?><td><?= e((string)$cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
