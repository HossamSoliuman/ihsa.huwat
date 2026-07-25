<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'stat_employee']);
$pageTitle   = 'داشبورد موظف الإحصاء';
$activeRoute = 'employee.php';

$pdo = db();

/* ------------------------------------------------------------
   تحديد الموظف وميناء عمله اليوم
------------------------------------------------------------ */
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->execute([$currentUserData['id']]);
$employeeId = (int)($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare(
    "SELECT ea.port_id, p.name AS port_name FROM employee_assignments ea
     JOIN ports p ON p.id = ea.port_id
     WHERE ea.employee_id = ? AND ea.assignment_date = CURDATE()
     ORDER BY ea.id DESC LIMIT 1"
);
$stmt->execute([$employeeId]);
$todayAssignment = $stmt->fetch();
$portId = $todayAssignment ? (int)$todayAssignment['port_id'] : 0;

$redirectUrl = BASE_URL . '/dashboard/employee.php';

/* ------------------------------------------------------------
   الإجراءات
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'start_counting') {
            $tripId = (int)$_POST['trip_id'];
            $stmt = $pdo->prepare(
                "UPDATE trips SET assigned_employee_id = ?, status = 'counting', counting_started_at = NOW()
                 WHERE id = ? AND port_id = ? AND status IN ('arrived','waiting_employee')"
            );
            $stmt->execute([$employeeId, $tripId, $portId]);
            redirectWithMessage($redirectUrl, 'success', 'تم بدء الإحصاء لهذه الرحلة.');

        } elseif ($action === 'submit_catch') {
            $tripId = (int)$_POST['trip_id'];
            $speciesIds  = $_POST['species_id']  ?? [];
            $reportedArr = $_POST['reported_kg'] ?? [];
            $verifiedArr = $_POST['verified_kg']  ?? [];
            $boxesArr    = $_POST['boxes']        ?? [];

            $totalReported = 0.0;
            $totalVerified = 0.0;
            $rows = [];

            foreach ($speciesIds as $i => $sid) {
                $reported = (float)($reportedArr[$i] ?? 0);
                $verified = (float)($verifiedArr[$i] ?? 0);
                $boxes    = (int)($boxesArr[$i] ?? 0);
                if ($reported <= 0 && $verified <= 0) {
                    continue; // تجاهل الأصناف الفارغة
                }
                $unreported = ($reported <= 0 && $verified > 0) ? 1 : 0;
                $rows[] = [$tripId, (int)$sid, $reported, $verified, $boxes, $unreported];
                $totalReported += $reported;
                $totalVerified += $verified;
            }

            if (empty($rows)) {
                redirectWithMessage($redirectUrl, 'error', 'الرجاء إدخال كمية لصنف واحد على الأقل.');
            }

            $pdo->beginTransaction();

            // امسح أي بيانات سابقة لهذه الرحلة (في حال إعادة الإدخال) ثم أدخل الجديد
            $pdo->prepare("DELETE FROM catch_details WHERE trip_id = ?")->execute([$tripId]);

            $ins = $pdo->prepare(
                "INSERT INTO catch_details (trip_id, species_id, captain_reported_kg, verified_kg, boxes_count, is_unreported_by_captain)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($rows as $r) {
                $ins->execute($r);
            }

            $diffKg = round($totalVerified - $totalReported, 2);
            $diffPercent = $totalReported > 0 ? round(($diffKg / $totalReported) * 100, 2) : ($totalVerified > 0 ? 100 : 0);
            $severity = discrepancySeverity($diffPercent);

            $newStatus = 'approved';
            if (in_array($severity, ['medium', 'major'], true)) {
                $newStatus = 'pending_review';
            }

            $pdo->prepare(
                "UPDATE trips SET captain_reported_weight = ?, verified_weight = ?,
                 counting_ended_at = NOW(), status = ?
                 WHERE id = ? AND port_id = ?"
            )->execute([$totalReported, $totalVerified, $newStatus, $tripId, $portId]);

            if ($severity !== 'none') {
                $pdo->prepare(
                    "INSERT INTO trip_discrepancies (trip_id, diff_kg, diff_percent, severity, review_status)
                     VALUES (?, ?, ?, ?, ?)"
                )->execute([$tripId, $diffKg, $diffPercent, $severity, $newStatus === 'pending_review' ? 'pending' : 'approved']);
            }

            $pdo->commit();

            $msg = $newStatus === 'pending_review'
                ? 'تم حفظ بيانات المصيد. الفرق يتجاوز الحد المسموح وتم تحويل الرحلة لمراجعة المشرف.'
                : 'تم حفظ بيانات المصيد واعتماد الرحلة بنجاح.';
            redirectWithMessage($redirectUrl, 'success', $msg);
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Employee dashboard action error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'حدث خطأ أثناء تنفيذ الإجراء.');
    }
}

/* ------------------------------------------------------------
   البيانات المعروضة
------------------------------------------------------------ */
$kpi = [
    'expected' => 0, 'arrived' => 0, 'counting' => 0, 'approved_today' => 0,
    'diff_trips' => 0, 'total_weight' => 0, 'total_boxes' => 0, 'avg_diff' => 0,
];
$expectedTrips = [];
$arrivedTrips = [];
$countingTrips = [];
$fishSpecies = [];

if ($portId && $employeeId) {
    $fishSpecies = $pdo->query("SELECT id, name_ar FROM fish_species ORDER BY name_ar")->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name
         FROM trips t JOIN boats b ON b.id = t.boat_id JOIN captains c ON c.id = t.captain_id
         WHERE t.port_id = ? AND t.status = 'expected' ORDER BY t.expected_arrival"
    );
    $stmt->execute([$portId]);
    $expectedTrips = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name
         FROM trips t JOIN boats b ON b.id = t.boat_id JOIN captains c ON c.id = t.captain_id
         WHERE t.port_id = ? AND t.status IN ('arrived','waiting_employee')
           AND (t.assigned_employee_id IS NULL OR t.assigned_employee_id = ?)
         ORDER BY t.actual_arrival"
    );
    $stmt->execute([$portId, $employeeId]);
    $arrivedTrips = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name
         FROM trips t JOIN boats b ON b.id = t.boat_id JOIN captains c ON c.id = t.captain_id
         WHERE t.port_id = ? AND t.assigned_employee_id = ? AND t.status = 'counting'
         ORDER BY t.counting_started_at"
    );
    $stmt->execute([$portId, $employeeId]);
    $countingTrips = $stmt->fetchAll();

    $kpi['expected'] = count($expectedTrips);
    $kpi['arrived']  = count($arrivedTrips);
    $kpi['counting'] = count($countingTrips);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM trips WHERE assigned_employee_id = ? AND status IN ('approved','closed')
         AND DATE(approved_at) = CURDATE()"
    );
    $stmt->execute([$employeeId]);
    $kpi['approved_today'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT t.id) FROM trips t JOIN trip_discrepancies td ON td.trip_id = t.id
         WHERE t.assigned_employee_id = ? AND DATE(t.counting_ended_at) = CURDATE()"
    );
    $stmt->execute([$employeeId]);
    $kpi['diff_trips'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(verified_weight),0) FROM trips
         WHERE assigned_employee_id = ? AND status IN ('approved','closed') AND DATE(counting_ended_at) = CURDATE()"
    );
    $stmt->execute([$employeeId]);
    $kpi['total_weight'] = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(cd.boxes_count),0) FROM catch_details cd
         JOIN trips t ON t.id = cd.trip_id
         WHERE t.assigned_employee_id = ? AND DATE(t.counting_ended_at) = CURDATE()"
    );
    $stmt->execute([$employeeId]);
    $kpi['total_boxes'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT AVG(td.diff_percent) FROM trip_discrepancies td
         JOIN trips t ON t.id = td.trip_id
         WHERE t.assigned_employee_id = ? AND DATE(t.counting_ended_at) = CURDATE()"
    );
    $stmt->execute([$employeeId]);
    $kpi['avg_diff'] = (float)($stmt->fetchColumn() ?: 0);
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if (!$portId): ?>
    <div class="panel">
        <div class="placeholder-box">لا يوجد تعيين لك في أي ميناء لهذا اليوم. تواصل مع مشرف الميناء لإضافتك للمناوبة.</div>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">القوارب المتوقع وصولها</span><span class="stat-value"><?= numberAr($kpi['expected']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب التي وصلت</span><span class="stat-value"><?= numberAr($kpi['arrived']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">مصيد تحت الإحصاء</span><span class="stat-value"><?= numberAr($kpi['counting']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">مصيد تم اعتماده اليوم</span><span class="stat-value"><?= numberAr($kpi['approved_today']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">رحلات بها فروقات</span><span class="stat-value"><?= numberAr($kpi['diff_trips']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي وزن اليوم (كجم)</span><span class="stat-value"><?= numberAr($kpi['total_weight']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي الصناديق</span><span class="stat-value"><?= numberAr($kpi['total_boxes']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">متوسط نسبة الفرق</span><span class="stat-value"><?= numberAr($kpi['avg_diff'], 1) ?>%</span></div>
</div>

<!-- القوارب المتوقع وصولها -->
<div class="panel">
    <h3>القوارب المتوقع وصولها</h3>
    <?php if (empty($expectedTrips)): ?>
        <p class="panel-hint">لا توجد قوارب متوقعة حاليًا في ميناء <?= e($todayAssignment['port_name']) ?>.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الرحلة</th><th>القارب</th><th>الكابتن</th><th>الوصول المتوقع</th><th>إدخال الكابتن (كجم)</th></tr></thead>
        <tbody>
        <?php foreach ($expectedTrips as $t): ?>
            <tr>
                <td><?= e($t['trip_code']) ?></td>
                <td><?= e($t['boat_name']) ?></td>
                <td><?= e($t['captain_name']) ?></td>
                <td><?= e($t['expected_arrival']) ?></td>
                <td class="num"><?= $t['captain_reported_weight'] ? numberAr($t['captain_reported_weight']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- القوارب التي وصلت -->
<div class="panel">
    <h3>القوارب التي وصلت</h3>
    <?php if (empty($arrivedTrips)): ?>
        <p class="panel-hint">لا توجد قوارب واصلة بانتظار بدء الإحصاء.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الرحلة</th><th>القارب</th><th>الوصول</th><th>حالة الإحصاء</th><th>الإجراء</th></tr></thead>
        <tbody>
        <?php foreach ($arrivedTrips as $t): ?>
            <tr>
                <td><?= e($t['trip_code']) ?></td>
                <td><?= e($t['boat_name']) ?></td>
                <td><?= $t['actual_arrival'] ? e($t['actual_arrival']) : '—' ?></td>
                <td><span class="badge badge-info">لم يبدأ</span></td>
                <td>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="start_counting">
                        <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">بدء الإحصاء</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- مصيد تحت الإحصاء: نموذج تسجيل البيانات -->
<div class="panel">
    <h3>مصيد تحت الإحصاء - تسجيل البيانات</h3>
    <?php if (empty($countingTrips)): ?>
        <p class="panel-hint">لا توجد رحلات تحت الإحصاء حاليًا لديك.</p>
    <?php else: ?>
        <?php foreach ($countingTrips as $t): ?>
        <div style="border:1px solid var(--line); border-radius: var(--radius); padding:16px; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <strong><?= e($t['trip_code']) ?> — <?= e($t['boat_name']) ?></strong>
                <span class="badge badge-warning">تحت الإحصاء منذ <?= e($t['counting_started_at']) ?></span>
            </div>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="submit_catch">
                <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr><th>النوع</th><th>إدخال الكابتن (كجم)</th><th>الوزن الفعلي (كجم)</th><th>عدد الصناديق</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fishSpecies as $sp): ?>
                        <tr>
                            <td>
                                <?= e($sp['name_ar']) ?>
                                <input type="hidden" name="species_id[]" value="<?= (int)$sp['id'] ?>">
                            </td>
                            <td><input type="number" step="0.01" min="0" name="reported_kg[]" style="width:110px; padding:5px; border:1px solid var(--line); border-radius:6px;"></td>
                            <td><input type="number" step="0.01" min="0" name="verified_kg[]" style="width:110px; padding:5px; border:1px solid var(--line); border-radius:6px;"></td>
                            <td><input type="number" step="1" min="0" name="boxes[]" style="width:80px; padding:5px; border:1px solid var(--line); border-radius:6px;"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <p class="panel-hint">اترك الصنف فارغًا إن لم يكن ضمن هذه الرحلة. أي صنف بوزن فعلي بدون إدخال من الكابتن سيُسجَّل تلقائيًا كـ"صنف غير مسجل".</p>
                <button type="submit" class="btn btn-primary">حفظ واعتماد بيانات المصيد</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
