<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'gov_supervisor', 'port_supervisor', 'stat_employee']);
$pageTitle   = 'القوارب والرحلات';
$activeRoute = 'trips.php';

$pdo = db();
$role = $currentUserData['role_code'];

/* ------------------------------------------------------------
   الفلاتر
------------------------------------------------------------ */
$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$portFilter   = isset($_GET['port_id']) ? (int)$_GET['port_id'] : 0;

$statusOptions = [
    ''                 => 'كل الحالات',
    'expected'         => 'متوقعة',
    'arrived'          => 'واصلة',
    'waiting_employee' => 'بانتظار موظف',
    'counting'         => 'تحت الإحصاء',
    'pending_review'   => 'بانتظار مراجعة',
    'approved'         => 'معتمدة',
    'closed'           => 'مغلقة',
];

/* ------------------------------------------------------------
   بناء شرط النطاق حسب دور المستخدم (Row-level scoping)
------------------------------------------------------------ */
$scopeSql = '';
$scopeParams = [];

if ($role === 'port_supervisor') {
    $scopeSql = " AND t.port_id = ? ";
    $scopeParams[] = (int)$currentUserData['port_id'];
} elseif ($role === 'gov_supervisor') {
    $scopeSql = " AND p.governorate_id = ? ";
    $scopeParams[] = (int)$currentUserData['governorate_id'];
} elseif ($role === 'stat_employee') {
    $stmtEmp = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
    $stmtEmp->execute([$currentUserData['id']]);
    $empId = (int)($stmtEmp->fetchColumn() ?: 0);
    $scopeSql = " AND t.assigned_employee_id = ? ";
    $scopeParams[] = $empId;
}

// قائمة الموانئ المتاحة للفلتر (فقط لمن يديرون أكثر من ميناء)
$portsForFilter = [];
if (in_array($role, ['super_admin', 'gov_supervisor'], true)) {
    $sql = "SELECT p.id, p.name FROM ports p WHERE p.is_active = 1";
    $params = [];
    if ($role === 'gov_supervisor') {
        $sql .= " AND p.governorate_id = ?";
        $params[] = $currentUserData['governorate_id'];
    }
    $sql .= " ORDER BY p.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $portsForFilter = $stmt->fetchAll();
}

/* ------------------------------------------------------------
   استعلام الرحلات الرئيسي
------------------------------------------------------------ */
$where = " WHERE DATE(COALESCE(t.actual_arrival, t.expected_arrival)) BETWEEN ? AND ? ";
$params = [$dateFrom, $dateTo];
$params = array_merge($params, $scopeParams);

$sql = "SELECT t.*, p.name AS port_name, b.name AS boat_name, c.full_name AS captain_name,
               u.full_name AS employee_name
        FROM trips t
        JOIN ports p ON p.id = t.port_id
        JOIN boats b ON b.id = t.boat_id
        JOIN captains c ON c.id = t.captain_id
        LEFT JOIN employees e ON e.id = t.assigned_employee_id
        LEFT JOIN users u ON u.id = e.user_id
        $where $scopeSql";

if ($statusFilter !== '' && array_key_exists($statusFilter, $statusOptions)) {
    $sql .= " AND t.status = ? ";
    $params[] = $statusFilter;
}
if ($portFilter > 0) {
    $sql .= " AND t.port_id = ? ";
    $params[] = $portFilter;
}

$sql .= " ORDER BY COALESCE(t.actual_arrival, t.expected_arrival) DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

/* ------------------------------------------------------------
   بطاقات المؤشرات (على نفس نطاق الصلاحية، لنفس المدى الزمني)
------------------------------------------------------------ */
$kpiSql = "SELECT
    SUM(t.status = 'expected') AS c_expected,
    SUM(t.status IN ('arrived','waiting_employee')) AS c_arrived,
    SUM(t.status = 'waiting_employee') AS c_waiting_emp,
    SUM(t.status = 'counting') AS c_counting,
    SUM(t.status = 'pending_review') AS c_pending_review,
    SUM(t.status = 'approved') AS c_approved,
    SUM(t.status = 'closed') AS c_closed,
    AVG(TIMESTAMPDIFF(MINUTE, t.counting_started_at, t.counting_ended_at)) AS avg_count_minutes
    FROM trips t
    JOIN ports p ON p.id = t.port_id
    $where $scopeSql";

$kpiParams = array_merge([$dateFrom, $dateTo], $scopeParams);
$stmt = $pdo->prepare($kpiSql);
$stmt->execute($kpiParams);
$kpi = $stmt->fetch();

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
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">الحالة</label>
        <select name="status" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
            <?php foreach ($statusOptions as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= $val === $statusFilter ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
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
    <div class="kpi-card"><span class="stat-label">القوارب في الطريق</span><span class="stat-value"><?= numberAr($kpi['c_expected']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب التي وصلت</span><span class="stat-value"><?= numberAr($kpi['c_arrived']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">بانتظار موظف الإحصاء</span><span class="stat-value"><?= numberAr($kpi['c_waiting_emp']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">تحت الإحصاء</span><span class="stat-value"><?= numberAr($kpi['c_counting']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">بانتظار مراجعة الفرق</span><span class="stat-value"><?= numberAr($kpi['c_pending_review']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الرحلات المعتمدة</span><span class="stat-value"><?= numberAr($kpi['c_approved']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الرحلات المغلقة</span><span class="stat-value"><?= numberAr($kpi['c_closed']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">متوسط زمن الإحصاء (دقيقة)</span><span class="stat-value"><?= numberAr($kpi['avg_count_minutes']) ?></span></div>
</div>

<!-- جدول الرحلات المباشر -->
<div class="panel">
    <h3>جدول الرحلات</h3>
    <p class="panel-hint">عرض حتى 300 رحلة ضمن المدى الزمني والفلاتر المحددة.</p>

    <?php if (empty($trips)): ?>
        <p class="panel-hint">لا توجد رحلات مطابقة لهذه الفلاتر.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
        <tr>
            <th>الرحلة</th><th>الميناء</th><th>القارب</th><th>الكابتن</th>
            <th>الوصول المتوقع</th><th>الوصول الفعلي</th>
            <th>موظف الإحصاء</th><th>الحالة</th>
            <th>الوزن المبلغ (كجم)</th><th>الوزن الفعلي (كجم)</th>
            <th>الفرق</th><th>الإجراء المطلوب</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($trips as $t):
            $reported = (float)($t['captain_reported_weight'] ?? 0);
            $verified = $t['verified_weight'] !== null ? (float)$t['verified_weight'] : null;
            $diffKg = $verified !== null ? round($verified - $reported, 2) : null;
            $diffPercent = ($verified !== null && $reported > 0) ? round((($verified - $reported) / $reported) * 100, 1) : null;

            $statusBadge = match ($t['status']) {
                'expected'         => 'badge-muted',
                'arrived', 'waiting_employee' => 'badge-info',
                'counting'         => 'badge-warning',
                'pending_review'   => 'badge-danger',
                'approved', 'closed' => 'badge-success',
                default => 'badge-muted',
            };

            $requiredAction = match ($t['status']) {
                'expected'         => 'بانتظار الوصول',
                'arrived', 'waiting_employee' => 'إسناد موظف',
                'counting'         => 'إتمام الإحصاء',
                'pending_review'   => 'اعتماد المشرف',
                'approved'         => 'إغلاق الرحلة',
                'closed'           => '—',
                default => '—',
            };
        ?>
            <tr>
                <td><?= e($t['trip_code']) ?></td>
                <td><?= e($t['port_name']) ?></td>
                <td><?= e($t['boat_name']) ?></td>
                <td><?= e($t['captain_name']) ?></td>
                <td><?= $t['expected_arrival'] ? e($t['expected_arrival']) : '—' ?></td>
                <td><?= $t['actual_arrival'] ? e($t['actual_arrival']) : '—' ?></td>
                <td><?= $t['employee_name'] ? e($t['employee_name']) : '—' ?></td>
                <td><span class="badge <?= $statusBadge ?>"><?= e($statusOptions[$t['status']] ?? $t['status']) ?></span></td>
                <td class="num"><?= $reported ? numberAr($reported) : '—' ?></td>
                <td class="num"><?= $verified !== null ? numberAr($verified) : '—' ?></td>
                <td class="num">
                    <?php if ($diffKg !== null): ?>
                        <?= numberAr($diffKg) ?> كجم (<?= numberAr($diffPercent, 1) ?>%)
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= e($requiredAction) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
