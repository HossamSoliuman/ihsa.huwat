<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'port_supervisor', 'quality_supervisor']);
$pageTitle   = 'الفروقات وجودة البيانات';
$activeRoute = 'discrepancies.php';

$pdo = db();
$role = $currentUserData['role_code'];

/* ------------------------------------------------------------
   الفلاتر
------------------------------------------------------------ */
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');
$portFilter = isset($_GET['port_id']) ? (int)$_GET['port_id'] : 0;

/* ------------------------------------------------------------
   نطاق الصلاحية
------------------------------------------------------------ */
$scopeSql = '';
$scopeParams = [];
if ($role === 'port_supervisor') {
    $scopeSql = " AND t.port_id = ? ";
    $scopeParams[] = (int)$currentUserData['port_id'];
}

$portsForFilter = [];
if ($role !== 'port_supervisor') {
    $portsForFilter = $pdo->query("SELECT id, name FROM ports WHERE is_active = 1 ORDER BY name")->fetchAll();
}

$redirectUrl = BASE_URL . '/dashboard/discrepancies.php?date_from=' . urlencode($dateFrom)
    . '&date_to=' . urlencode($dateTo) . ($portFilter ? '&port_id=' . $portFilter : '');

/* ------------------------------------------------------------
   إجراء: اعتماد فرق كبير من هذه اللوحة أيضًا
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_discrepancy') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $discrepancyId = (int)$_POST['discrepancy_id'];
    $tripId        = (int)$_POST['trip_id'];

    try {
        $pdo->beginTransaction();
        $pdo->prepare(
            "UPDATE trip_discrepancies SET review_status = 'approved',
             reviewed_by = ?, reviewed_at = NOW() WHERE id = ?"
        )->execute([$currentUserData['id'], $discrepancyId]);

        $pdo->prepare(
            "UPDATE trips SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?"
        )->execute([$currentUserData['id'], $tripId]);
        $pdo->commit();
        redirectWithMessage($redirectUrl, 'success', 'تم اعتماد الفرق بنجاح.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Discrepancy approval error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'حدث خطأ أثناء الاعتماد.');
    }
}

$portFilterSql = '';
if ($portFilter > 0) {
    $portFilterSql = " AND t.port_id = ? ";
}

/* ------------------------------------------------------------
   المؤشرات الرئيسية
------------------------------------------------------------ */
$kpiSql = "SELECT
    COUNT(DISTINCT td.trip_id) AS total_trips_with_diff,
    SUM(td.severity = 'minor')  AS c_minor,
    SUM(td.severity = 'medium') AS c_medium,
    SUM(td.severity = 'major')  AS c_major,
    AVG(td.diff_percent) AS avg_diff_percent,
    SUM(ABS(td.diff_kg)) AS total_diff_kg,
    SUM(td.review_status != 'approved') AS c_pending
    FROM trip_discrepancies td
    JOIN trips t ON t.id = td.trip_id
    WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
    $scopeSql $portFilterSql";

$params = [$dateFrom, $dateTo];
$params = array_merge($params, $scopeParams);
if ($portFilter > 0) $params[] = $portFilter;

$stmt = $pdo->prepare($kpiSql);
$stmt->execute($params);
$kpi = $stmt->fetch();

$unreportedCount = 0;
$sql = "SELECT COUNT(*) FROM catch_details cd
        JOIN trips t ON t.id = cd.trip_id
        WHERE cd.is_unreported_by_captain = 1
          AND t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
          $scopeSql $portFilterSql";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$unreportedCount = (int)$stmt->fetchColumn();

/* ------------------------------------------------------------
   أعلى القوارب في الفروقات
------------------------------------------------------------ */
$sql = "SELECT b.name AS boat_name, COUNT(DISTINCT td.trip_id) AS trips_count,
               AVG(td.diff_percent) AS avg_diff
        FROM trip_discrepancies td
        JOIN trips t ON t.id = td.trip_id
        JOIN boats b ON b.id = t.boat_id
        WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
        $scopeSql $portFilterSql
        GROUP BY b.id, b.name ORDER BY trips_count DESC LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topBoats = $stmt->fetchAll();

/* ------------------------------------------------------------
   أعلى الكباتن في الفروقات
------------------------------------------------------------ */
$sql = "SELECT c.full_name AS captain_name, COUNT(DISTINCT td.trip_id) AS trips_count,
               AVG(td.diff_percent) AS avg_diff
        FROM trip_discrepancies td
        JOIN trips t ON t.id = td.trip_id
        JOIN captains c ON c.id = t.captain_id
        WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
        $scopeSql $portFilterSql
        GROUP BY c.id, c.full_name ORDER BY trips_count DESC LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topCaptains = $stmt->fetchAll();

/* ------------------------------------------------------------
   أنواع السمك الأكثر اختلافًا
------------------------------------------------------------ */
$sql = "SELECT fs.name_ar, SUM(ABS(cd.verified_kg - cd.captain_reported_kg)) AS diff_kg
        FROM catch_details cd
        JOIN fish_species fs ON fs.id = cd.species_id
        JOIN trips t ON t.id = cd.trip_id
        WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
        $scopeSql $portFilterSql
        GROUP BY fs.id, fs.name_ar
        HAVING diff_kg > 0
        ORDER BY diff_kg DESC LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topSpecies = $stmt->fetchAll();

/* ------------------------------------------------------------
   أسباب الفروقات الأكثر تكرارًا
------------------------------------------------------------ */
$sql = "SELECT COALESCE(NULLIF(td.reason,''), 'غير محدد') AS reason, COUNT(*) AS c
        FROM trip_discrepancies td
        JOIN trips t ON t.id = td.trip_id
        WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
        $scopeSql $portFilterSql
        GROUP BY reason ORDER BY c DESC LIMIT 6";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topReasons = $stmt->fetchAll();

/* ------------------------------------------------------------
   الفروقات حسب الميناء
------------------------------------------------------------ */
$sql = "SELECT p.name AS port_name, COUNT(DISTINCT td.trip_id) AS trips_count
        FROM trip_discrepancies td
        JOIN trips t ON t.id = td.trip_id
        JOIN ports p ON p.id = t.port_id
        WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
        $scopeSql $portFilterSql
        GROUP BY p.id, p.name ORDER BY trips_count DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$byPort = $stmt->fetchAll();

/* ------------------------------------------------------------
   الفروقات حسب موظف الإحصاء
------------------------------------------------------------ */
$sql = "SELECT u.full_name AS employee_name, COUNT(DISTINCT td.trip_id) AS trips_count,
               AVG(td.diff_percent) AS avg_diff
        FROM trip_discrepancies td
        JOIN trips t ON t.id = td.trip_id
        LEFT JOIN employees e ON e.id = t.assigned_employee_id
        LEFT JOIN users u ON u.id = e.user_id
        WHERE t.actual_arrival IS NOT NULL AND DATE(t.actual_arrival) BETWEEN ? AND ?
        $scopeSql $portFilterSql
        GROUP BY u.id, u.full_name ORDER BY trips_count DESC LIMIT 8";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$byEmployee = $stmt->fetchAll();

/* ------------------------------------------------------------
   الرحلات المحتاجة لاعتماد المشرف
------------------------------------------------------------ */
$sql = "SELECT t.id AS trip_id, t.trip_code, p.name AS port_name, b.name AS boat_name,
               td.id AS discrepancy_id, td.diff_kg, td.diff_percent, td.severity, td.reason
        FROM trip_discrepancies td
        JOIN trips t ON t.id = td.trip_id
        JOIN ports p ON p.id = t.port_id
        JOIN boats b ON b.id = t.boat_id
        WHERE td.review_status != 'approved'
        $scopeSql $portFilterSql
        ORDER BY td.diff_percent DESC LIMIT 100";
// ملاحظة: هذا الاستعلام لا يُقيَّد بالتاريخ عمدًا (يعرض كل ما هو معلّق بغض النظر عن التاريخ)
$pendingParams = $scopeParams;
if ($portFilter > 0) $pendingParams[] = $portFilter;
$stmt = $pdo->prepare($sql);
$stmt->execute($pendingParams);
$pendingTrips = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<!-- الفلاتر -->
<form method="get" class="panel" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; padding:16px 20px;">
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">من تاريخ</label>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
    </div>
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">إلى تاريخ</label>
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
    </div>
    <?php if (!empty($portsForFilter)): ?>
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">الميناء</label>
        <select name="port_id" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
            <option value="0">كل الموانئ</option>
            <?php foreach ($portsForFilter as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $portFilter ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
</form>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">الرحلات ذات الفروقات</span><span class="stat-value"><?= numberAr($kpi['total_trips_with_diff']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">فروقات بسيطة (3-5%)</span><span class="stat-value"><?= numberAr($kpi['c_minor']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">فروقات متوسطة (5-10%)</span><span class="stat-value"><?= numberAr($kpi['c_medium']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">فروقات كبيرة (أكثر من 10%)</span><span class="stat-value"><?= numberAr($kpi['c_major']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">أصناف غير مسجلة من الكابتن</span><span class="stat-value"><?= numberAr($unreportedCount) ?></span></div>
    <div class="kpi-card"><span class="stat-label">متوسط نسبة الفرق</span><span class="stat-value"><?= numberAr($kpi['avg_diff_percent'], 1) ?>%</span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي فروقات الوزن (كجم)</span><span class="stat-value"><?= numberAr($kpi['total_diff_kg']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">بانتظار المراجعة</span><span class="stat-value"><?= numberAr($kpi['c_pending']) ?></span></div>
</div>

<div class="grid-3">
    <div class="panel">
        <h3>أعلى القوارب في الفروقات</h3>
        <?php if (empty($topBoats)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>القارب</th><th>عدد الرحلات</th><th>متوسط الفرق</th></tr></thead>
            <tbody>
            <?php foreach ($topBoats as $b): ?>
                <tr><td><?= e($b['boat_name']) ?></td><td class="num"><?= numberAr($b['trips_count']) ?></td><td class="num"><?= numberAr($b['avg_diff'],1) ?>%</td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>أعلى الكباتن في الفروقات</h3>
        <?php if (empty($topCaptains)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>الكابتن</th><th>عدد الرحلات</th><th>متوسط الفرق</th></tr></thead>
            <tbody>
            <?php foreach ($topCaptains as $c): ?>
                <tr><td><?= e($c['captain_name']) ?></td><td class="num"><?= numberAr($c['trips_count']) ?></td><td class="num"><?= numberAr($c['avg_diff'],1) ?>%</td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>أنواع السمك الأكثر اختلافًا</h3>
        <?php if (empty($topSpecies)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>النوع</th><th>فرق الوزن (كجم)</th></tr></thead>
            <tbody>
            <?php foreach ($topSpecies as $s): ?>
                <tr><td><?= e($s['name_ar']) ?></td><td class="num"><?= numberAr($s['diff_kg']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="grid-3">
    <div class="panel">
        <h3>أسباب الفروقات الأكثر تكرارًا</h3>
        <?php if (empty($topReasons)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>السبب</th><th>عدد المرات</th></tr></thead>
            <tbody>
            <?php foreach ($topReasons as $r): ?>
                <tr><td><?= e($r['reason']) ?></td><td class="num"><?= numberAr($r['c']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>الفروقات حسب الميناء</h3>
        <?php if (empty($byPort)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>الميناء</th><th>عدد الرحلات</th></tr></thead>
            <tbody>
            <?php foreach ($byPort as $p): ?>
                <tr><td><?= e($p['port_name']) ?></td><td class="num"><?= numberAr($p['trips_count']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>الفروقات حسب موظف الإحصاء</h3>
        <?php if (empty($byEmployee)): ?><p class="panel-hint">لا توجد بيانات كافية.</p><?php else: ?>
        <table>
            <thead><tr><th>الموظف</th><th>عدد الرحلات</th><th>متوسط الفرق</th></tr></thead>
            <tbody>
            <?php foreach ($byEmployee as $emp): ?>
                <tr><td><?= $emp['employee_name'] ? e($emp['employee_name']) : '—' ?></td><td class="num"><?= numberAr($emp['trips_count']) ?></td><td class="num"><?= numberAr($emp['avg_diff'],1) ?>%</td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- الرحلات المحتاجة لاعتماد المشرف -->
<div class="panel">
    <h3>الرحلات المحتاجة لاعتماد المشرف</h3>
    <p class="panel-hint">هذه القائمة تعرض كل الفروقات المعلّقة بغض النظر عن مدى التاريخ المحدد أعلاه.</p>
    <?php if (empty($pendingTrips)): ?>
        <p class="panel-hint">لا توجد فروقات بانتظار الاعتماد حاليًا. ✅</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الرحلة</th><th>الميناء</th><th>القارب</th><th>الفرق (كجم)</th><th>النسبة</th><th>التصنيف</th><th>السبب</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($pendingTrips as $t): ?>
            <tr>
                <td><?= e($t['trip_code']) ?></td>
                <td><?= e($t['port_name']) ?></td>
                <td><?= e($t['boat_name']) ?></td>
                <td class="num"><?= numberAr($t['diff_kg']) ?></td>
                <td class="num"><?= numberAr($t['diff_percent'], 1) ?>%</td>
                <td><span class="badge <?= severityBadgeClass($t['severity']) ?>"><?= severityLabel($t['severity']) ?></span></td>
                <td><?= $t['reason'] ? e($t['reason']) : '—' ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('تأكيد اعتماد هذا الفرق؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="approve_discrepancy">
                        <input type="hidden" name="trip_id" value="<?= (int)$t['trip_id'] ?>">
                        <input type="hidden" name="discrepancy_id" value="<?= (int)$t['discrepancy_id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">اعتماد</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
