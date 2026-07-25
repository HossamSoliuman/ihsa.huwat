<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'hr_manager', 'gov_supervisor']);
$pageTitle   = 'أداء موظفي الإحصاء';
$activeRoute = 'employee_performance.php';

$pdo = db();
$role = $currentUserData['role_code'];

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');

$scopeSql = '';
$scopeParams = [];
if ($role === 'gov_supervisor') {
    $scopeSql = " AND p.governorate_id = ? ";
    $scopeParams[] = (int)$currentUserData['governorate_id'];
}

$params = [];
for ($i = 0; $i < 3; $i++) {
    $params[] = $dateFrom;
    $params[] = $dateTo;
    foreach ($scopeParams as $sp) $params[] = $sp;
}

/* ------------------------------------------------------------
   جدول الأداء لكل موظف
   (نستخدم subqueries مجمّعة مسبقًا لتفادي تضخّم الصفوف عند وجود
    أكثر من فرق أو أكثر من مرفق لنفس الرحلة)
------------------------------------------------------------ */
$sql = "SELECT e.id AS employee_id, u.full_name,
               (SELECT p2.name FROM employee_assignments ea2 JOIN ports p2 ON p2.id = ea2.port_id
                    WHERE ea2.employee_id = e.id ORDER BY ea2.assignment_date DESC LIMIT 1) AS last_port,
               COALESCE(tr.trips_count, 0) AS trips_count,
               COALESCE(tr.total_weight, 0) AS total_weight,
               tr.avg_minutes,
               COALESCE(tr.edits_after_approval, 0) AS edits_after_approval,
               COALESCE(dc.diff_trips, 0) AS diff_trips,
               dc.avg_diff_percent,
               COALESCE(ac.trips_with_attachments, 0) AS trips_with_attachments
        FROM employees e
        JOIN users u ON u.id = e.user_id
        LEFT JOIN (
            SELECT t.assigned_employee_id AS emp_id, COUNT(*) AS trips_count,
                   SUM(t.verified_weight) AS total_weight,
                   AVG(TIMESTAMPDIFF(MINUTE, t.counting_started_at, t.counting_ended_at)) AS avg_minutes,
                   SUM(t.edited_after_approval) AS edits_after_approval
            FROM trips t
            JOIN ports p ON p.id = t.port_id
            WHERE t.status IN ('approved','closed') AND t.actual_arrival IS NOT NULL
              AND DATE(t.actual_arrival) BETWEEN ? AND ? $scopeSql
            GROUP BY t.assigned_employee_id
        ) tr ON tr.emp_id = e.id
        LEFT JOIN (
            SELECT t.assigned_employee_id AS emp_id, COUNT(DISTINCT td.trip_id) AS diff_trips,
                   AVG(td.diff_percent) AS avg_diff_percent
            FROM trips t
            JOIN trip_discrepancies td ON td.trip_id = t.id
            JOIN ports p ON p.id = t.port_id
            WHERE t.status IN ('approved','closed') AND t.actual_arrival IS NOT NULL
              AND DATE(t.actual_arrival) BETWEEN ? AND ? $scopeSql
            GROUP BY t.assigned_employee_id
        ) dc ON dc.emp_id = e.id
        LEFT JOIN (
            SELECT t.assigned_employee_id AS emp_id, COUNT(DISTINCT ta.trip_id) AS trips_with_attachments
            FROM trips t
            JOIN trip_attachments ta ON ta.trip_id = t.id
            JOIN ports p ON p.id = t.port_id
            WHERE t.status IN ('approved','closed') AND t.actual_arrival IS NOT NULL
              AND DATE(t.actual_arrival) BETWEEN ? AND ? $scopeSql
            GROUP BY t.assigned_employee_id
        ) ac ON ac.emp_id = e.id
        WHERE e.status != 'terminated'
        HAVING trips_count > 0
        ORDER BY trips_count DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$performanceRows = $stmt->fetchAll();

/* ------------------------------------------------------------
   حساب التقييم ونسبة اكتمال المرفقات لكل صف
------------------------------------------------------------ */
foreach ($performanceRows as &$row) {
    $row['attachment_completion'] = $row['trips_count'] > 0
        ? round(($row['trips_with_attachments'] / $row['trips_count']) * 100, 1)
        : 0;

    $avgDiff = (float)($row['avg_diff_percent'] ?? 0);
    $edits = (int)$row['edits_after_approval'];

    if ($avgDiff <= 3 && $edits === 0) {
        $row['rating'] = 'ممتاز'; $row['rating_class'] = 'badge-success';
    } elseif ($avgDiff <= 5 && $edits <= 1) {
        $row['rating'] = 'جيد جدًا'; $row['rating_class'] = 'badge-info';
    } elseif ($avgDiff <= 10) {
        $row['rating'] = 'جيد'; $row['rating_class'] = 'badge-warning';
    } else {
        $row['rating'] = 'يحتاج تحسين'; $row['rating_class'] = 'badge-danger';
    }
}
unset($row);

/* ------------------------------------------------------------
   المؤشرات الرئيسية
------------------------------------------------------------ */
$totalApprovedTrips = array_sum(array_column($performanceRows, 'trips_count'));
$totalWeight = array_sum(array_column($performanceRows, 'total_weight'));
$totalDiffTrips = array_sum(array_column($performanceRows, 'diff_trips'));
$avgTimeAll = 0;
$timeSamples = array_filter(array_column($performanceRows, 'avg_minutes'));
if (!empty($timeSamples)) $avgTimeAll = array_sum($timeSamples) / count($timeSamples);

$avgAttachmentCompletion = 0;
if (!empty($performanceRows)) {
    $avgAttachmentCompletion = array_sum(array_column($performanceRows, 'attachment_completion')) / count($performanceRows);
}
$totalEdits = array_sum(array_column($performanceRows, 'edits_after_approval'));

$topPerformer = null;
$topQuality = null;
if (!empty($performanceRows)) {
    $sorted = $performanceRows;
    usort($sorted, fn($a, $b) => $b['trips_count'] <=> $a['trips_count']);
    $topPerformer = $sorted[0];

    $qualityCandidates = array_filter($performanceRows, fn($r) => $r['trips_count'] > 0);
    if (!empty($qualityCandidates)) {
        usort($qualityCandidates, fn($a, $b) => (float)($a['avg_diff_percent'] ?? 999) <=> (float)($b['avg_diff_percent'] ?? 999));
        $topQuality = array_values($qualityCandidates)[0];
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<form method="get" class="panel" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; padding:16px 20px;">
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">من تاريخ</label>
        <input type="date" name="date_from" value="<?= e($dateFrom) ?>" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
    </div>
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">إلى تاريخ</label>
        <input type="date" name="date_to" value="<?= e($dateTo) ?>" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
    </div>
    <button type="submit" class="btn btn-primary">تطبيق</button>
</form>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">إجمالي الرحلات المعتمدة</span><span class="stat-value"><?= numberAr($totalApprovedTrips) ?></span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي المصيد المحصى (كجم)</span><span class="stat-value"><?= numberAr($totalWeight) ?></span></div>
    <div class="kpi-card"><span class="stat-label">متوسط وقت الإحصاء (دقيقة)</span><span class="stat-value"><?= numberAr($avgTimeAll) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">الرحلات ذات الفروقات</span><span class="stat-value"><?= numberAr($totalDiffTrips) ?></span></div>
    <div class="kpi-card"><span class="stat-label">نسبة اكتمال المرفقات</span><span class="stat-value"><?= numberAr($avgAttachmentCompletion,1) ?>%</span></div>
    <div class="kpi-card <?= $totalEdits > 0 ? 'alert-tone' : '' ?>"><span class="stat-label">التعديلات بعد الاعتماد</span><span class="stat-value"><?= numberAr($totalEdits) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الأعلى إنجازًا</span><span class="stat-value" style="font-size:16px;"><?= $topPerformer ? e($topPerformer['full_name']) : '—' ?></span></div>
    <div class="kpi-card"><span class="stat-label">الأعلى جودة</span><span class="stat-value" style="font-size:16px;"><?= $topQuality ? e($topQuality['full_name']) : '—' ?></span></div>
</div>

<div class="panel">
    <h3>جدول أداء الموظفين</h3>
    <p class="panel-hint">يشمل فقط الموظفين الذين لديهم رحلات معتمدة ضمن المدى الزمني المحدد.</p>
    <?php if (empty($performanceRows)): ?>
        <p class="panel-hint">لا توجد بيانات أداء كافية لهذا المدى الزمني.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
        <tr>
            <th>الموظف</th><th>آخر ميناء</th><th>عدد الرحلات</th><th>الكمية (كجم)</th>
            <th>متوسط الوقت (دقيقة)</th><th>اكتمال المرفقات</th><th>رحلات بها فروقات</th><th>التقييم</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($performanceRows as $row): ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
                <td><?= $row['last_port'] ? e($row['last_port']) : '—' ?></td>
                <td class="num"><?= numberAr($row['trips_count']) ?></td>
                <td class="num"><?= numberAr($row['total_weight']) ?></td>
                <td class="num"><?= $row['avg_minutes'] !== null ? numberAr($row['avg_minutes']) : '—' ?></td>
                <td class="num"><?= numberAr($row['attachment_completion'],1) ?>%</td>
                <td class="num"><?= numberAr($row['diff_trips']) ?></td>
                <td><span class="badge <?= $row['rating_class'] ?>"><?= e($row['rating']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
