<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'gov_supervisor', 'port_supervisor']);
$pageTitle   = 'لوحة الميناء';
$activeRoute = 'port.php';

$pdo = db();

/* ------------------------------------------------------------
   تحديد الميناء المعروض:
   - مشرف الميناء: يُقيَّد تلقائيًا بميناءه.
   - الإدارة العليا / مشرف المحافظة: يختار الميناء من قائمة منسدلة.
------------------------------------------------------------ */
if ($currentUserData['role_code'] === 'port_supervisor') {
    $portId = (int)$currentUserData['port_id'];
} else {
    $portId = isset($_GET['port_id']) ? (int)$_GET['port_id'] : 0;
}

$portsList = [];
if ($currentUserData['role_code'] !== 'port_supervisor') {
    $sql = "SELECT p.id, p.name, g.name AS gov_name FROM ports p
            JOIN governorates g ON g.id = p.governorate_id
            WHERE p.is_active = 1";
    $params = [];
    if ($currentUserData['role_code'] === 'gov_supervisor') {
        $sql .= " AND p.governorate_id = ?";
        $params[] = $currentUserData['governorate_id'];
    }
    $sql .= " ORDER BY p.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $portsList = $stmt->fetchAll();

    if (!$portId && !empty($portsList)) {
        $portId = (int)$portsList[0]['id'];
    }
}

/* ------------------------------------------------------------
   معالجة الإجراءات (POST) - حفظ عادي مع إعادة توجيه (PRG Pattern)
------------------------------------------------------------ */
$redirectUrl = BASE_URL . '/dashboard/port.php' . ($portId ? '?port_id=' . $portId : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {

            // إسناد رحلة لموظف إحصاء متاح
            case 'assign_trip':
                $tripId     = (int)$_POST['trip_id'];
                $employeeId = (int)$_POST['employee_id'];
                $stmt = $pdo->prepare(
                    "UPDATE trips SET assigned_employee_id = ?, status = 'counting',
                     counting_started_at = NOW()
                     WHERE id = ? AND port_id = ?"
                );
                $stmt->execute([$employeeId, $tripId, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تم إسناد الرحلة للموظف بنجاح.');
                break;

            // تغيير الموظف المسؤول عن رحلة
            case 'change_employee':
                $tripId     = (int)$_POST['trip_id'];
                $employeeId = (int)$_POST['employee_id'];
                $stmt = $pdo->prepare(
                    "UPDATE trips SET assigned_employee_id = ? WHERE id = ? AND port_id = ?"
                );
                $stmt->execute([$employeeId, $tripId, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تم تغيير الموظف المسؤول عن الرحلة.');
                break;

            // تحويل رحلة للمراجعة (فروقات)
            case 'transfer_to_review':
                $tripId = (int)$_POST['trip_id'];
                $stmt = $pdo->prepare(
                    "UPDATE trips SET status = 'pending_review' WHERE id = ? AND port_id = ?"
                );
                $stmt->execute([$tripId, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تم تحويل الرحلة لقسم المراجعة.');
                break;

            // اعتماد فرق كبير من قبل المشرف
            case 'approve_large_diff':
                $discrepancyId = (int)$_POST['discrepancy_id'];
                $tripId        = (int)$_POST['trip_id'];

                $pdo->beginTransaction();
                $pdo->prepare(
                    "UPDATE trip_discrepancies SET review_status = 'approved',
                     reviewed_by = ?, reviewed_at = NOW() WHERE id = ?"
                )->execute([$currentUserData['id'], $discrepancyId]);

                $pdo->prepare(
                    "UPDATE trips SET status = 'approved', approved_by = ?, approved_at = NOW()
                     WHERE id = ? AND port_id = ?"
                )->execute([$currentUserData['id'], $tripId, $portId]);
                $pdo->commit();

                redirectWithMessage($redirectUrl, 'success', 'تم اعتماد الفرق واعتماد الرحلة.');
                break;

            // تعيين موظف بديل لمناوبة موظف غائب
            case 'assign_substitute':
                $shiftId    = (int)$_POST['shift_id'];
                $employeeId = (int)$_POST['substitute_employee_id'];
                $stmt = $pdo->prepare(
                    "INSERT INTO employee_assignments (employee_id, port_id, shift_id, assignment_date, is_temporary)
                     VALUES (?, ?, ?, CURDATE(), 1)"
                );
                $stmt->execute([$employeeId, $portId, $shiftId]);
                redirectWithMessage($redirectUrl, 'success', 'تم تعيين الموظف البديل للمناوبة.');
                break;

            // إضافة موظف جديد للمناوبة الحالية بهذا الميناء
            case 'add_employee_to_shift':
                $shiftId    = (int)$_POST['shift_id'];
                $employeeId = (int)$_POST['employee_id'];
                $stmt = $pdo->prepare(
                    "INSERT INTO employee_assignments (employee_id, port_id, shift_id, assignment_date, is_temporary)
                     VALUES (?, ?, ?, CURDATE(), 0)"
                );
                $stmt->execute([$employeeId, $portId, $shiftId]);
                redirectWithMessage($redirectUrl, 'success', 'تمت إضافة الموظف للمناوبة.');
                break;

            default:
                redirectWithMessage($redirectUrl, 'error', 'إجراء غير معروف.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Port dashboard action error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'حدث خطأ أثناء تنفيذ الإجراء. حاول مجددًا.');
    }
}

/* ------------------------------------------------------------
   جلب البيانات المعروضة
------------------------------------------------------------ */
$kpi = [
    'on_shift' => 0, 'available' => 0, 'busy' => 0,
    'expected' => 0, 'arrived' => 0, 'counting' => 0,
    'pending_review' => 0, 'avg_wait' => 0,
];
$employeeRows = [];
$expectedTrips = [];
$arrivedTrips = [];
$reviewTrips = [];
$shiftsList = [];
$availableEmployeesForPort = [];

if ($portId) {
    $shiftsList = $pdo->query("SELECT * FROM shifts ORDER BY start_time")->fetchAll();

    // موظفو المناوبة الحالية في هذا الميناء اليوم + حالتهم
    $stmt = $pdo->prepare(
        "SELECT e.id AS employee_id, u.full_name, s.name AS shift_name,
                a.status AS attendance_status,
                (SELECT t.trip_code FROM trips t
                    WHERE t.assigned_employee_id = e.id AND t.status IN ('waiting_employee','counting')
                    ORDER BY t.id DESC LIMIT 1) AS current_trip,
                (SELECT COUNT(*) FROM trips t2
                    WHERE t2.assigned_employee_id = e.id AND DATE(t2.created_at) = CURDATE()) AS trips_count
         FROM employee_assignments ea
         JOIN employees e ON e.id = ea.employee_id
         JOIN users u ON u.id = e.user_id
         JOIN shifts s ON s.id = ea.shift_id
         LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date = CURDATE() AND a.shift_id = ea.shift_id
         WHERE ea.port_id = ? AND ea.assignment_date = CURDATE()
         ORDER BY s.start_time, u.full_name"
    );
    $stmt->execute([$portId]);
    $employeeRows = $stmt->fetchAll();

    foreach ($employeeRows as $row) {
        $isPresent = ($row['attendance_status'] ?? null) === 'present';
        if ($isPresent) {
            $kpi['on_shift']++;
            if (!empty($row['current_trip'])) {
                $kpi['busy']++;
            } else {
                $kpi['available']++;
            }
        }
    }
    // قائمة الموظفين المتاحين فقط (لاستخدامها في قوائم إسناد الرحلات)
    $availableEmployeesForPort = array_filter($employeeRows, function ($r) {
        return ($r['attendance_status'] ?? null) === 'present' && empty($r['current_trip']);
    });

    // الرحلات المتوقعة (لم تصل بعد)
    $stmt = $pdo->prepare(
        "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name
         FROM trips t
         JOIN boats b ON b.id = t.boat_id
         JOIN captains c ON c.id = t.captain_id
         WHERE t.port_id = ? AND t.status = 'expected'
         ORDER BY t.expected_arrival"
    );
    $stmt->execute([$portId]);
    $expectedTrips = $stmt->fetchAll();
    $kpi['expected'] = count($expectedTrips);

    // القوارب الواصلة (وصلت، بانتظار موظف أو تحت الإحصاء)
    $stmt = $pdo->prepare(
        "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name,
                u.full_name AS employee_name
         FROM trips t
         JOIN boats b ON b.id = t.boat_id
         JOIN captains c ON c.id = t.captain_id
         LEFT JOIN employees e ON e.id = t.assigned_employee_id
         LEFT JOIN users u ON u.id = e.user_id
         WHERE t.port_id = ? AND t.status IN ('arrived','waiting_employee','counting')
         ORDER BY t.actual_arrival"
    );
    $stmt->execute([$portId]);
    $arrivedTrips = $stmt->fetchAll();
    $kpi['arrived']  = count(array_filter($arrivedTrips, fn($t) => in_array($t['status'], ['arrived','waiting_employee'], true)));
    $kpi['counting'] = count(array_filter($arrivedTrips, fn($t) => $t['status'] === 'counting'));

    // رحلات بانتظار مراجعة فرق (فروقات كبيرة تحتاج اعتماد المشرف)
    $stmt = $pdo->prepare(
        "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name,
                td.id AS discrepancy_id, td.diff_kg, td.diff_percent, td.severity
         FROM trips t
         JOIN boats b ON b.id = t.boat_id
         JOIN captains c ON c.id = t.captain_id
         JOIN trip_discrepancies td ON td.trip_id = t.id
         WHERE t.port_id = ? AND t.status = 'pending_review' AND td.review_status != 'approved'
         ORDER BY td.diff_percent DESC"
    );
    $stmt->execute([$portId]);
    $reviewTrips = $stmt->fetchAll();
    $kpi['pending_review'] = count($reviewTrips);

    // متوسط زمن الانتظار قبل بدء الإحصاء (بالدقائق) لليوم الحالي
    $avgWait = $pdo->prepare(
        "SELECT AVG(TIMESTAMPDIFF(MINUTE, actual_arrival, counting_started_at)) AS avg_min
         FROM trips
         WHERE port_id = ? AND DATE(actual_arrival) = CURDATE() AND counting_started_at IS NOT NULL"
    );
    $avgWait->execute([$portId]);
    $kpi['avg_wait'] = round((float)($avgWait->fetch()['avg_min'] ?? 0));
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if (!$portId): ?>
    <div class="panel">
        <div class="placeholder-box">لا يوجد ميناء مرتبط بحسابك حاليًا. تواصل مع الإدارة لضبط نطاق الصلاحية.</div>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php endif; ?>

<?php if (!empty($portsList)): ?>
<form method="get" class="panel" style="display:flex; align-items:center; gap:12px; padding:14px 18px;">
    <label style="font-weight:700; font-size:13.5px;">اختر الميناء:</label>
    <select name="port_id" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid var(--line);">
        <?php foreach ($portsList as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $portId ? 'selected' : '' ?>>
                <?= e($p['name']) ?> — <?= e($p['gov_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>

<div style="display:flex; justify-content:flex-end; margin:-4px 0 18px;">
    <a class="btn btn-outline" href="<?= BASE_URL ?>/dashboard/harbor_details.php?port_id=<?= $portId ?>">عرض تفاصيل المرفأ</a>
</div>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">الموظفون في المناوبة</span><span class="stat-value"><?= numberAr($kpi['on_shift']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون المتاحون</span><span class="stat-value"><?= numberAr($kpi['available']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون المشغولون</span><span class="stat-value"><?= numberAr($kpi['busy']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب المتوقعة</span><span class="stat-value"><?= numberAr($kpi['expected']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب الواصلة</span><span class="stat-value"><?= numberAr($kpi['arrived']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">تحت الإحصاء</span><span class="stat-value"><?= numberAr($kpi['counting']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">بانتظار مراجعة</span><span class="stat-value"><?= numberAr($kpi['pending_review']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">متوسط انتظار القارب (دقيقة)</span><span class="stat-value"><?= numberAr($kpi['avg_wait']) ?></span></div>
</div>

<!-- الجدول الرئيسي: الموظفون -->
<div class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>موظفو المناوبة الحالية</h3>
        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('addShiftForm').classList.toggle('open-form')">+ إضافة موظف للمناوبة</button>
    </div>

    <form method="post" id="addShiftForm" class="hidden-form" style="display:none; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_employee_to_shift">
        <select name="employee_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
            <option value="">اختر الموظف</option>
            <?php
            $allEmployees = $pdo->query("SELECT e.id, u.full_name FROM employees e JOIN users u ON u.id = e.user_id WHERE e.status = 'active' ORDER BY u.full_name")->fetchAll();
            foreach ($allEmployees as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= e($emp['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="shift_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
            <?php foreach ($shiftsList as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">إضافة</button>
    </form>

    <?php if (empty($employeeRows)): ?>
        <p class="panel-hint">لا يوجد موظفون مسندون لهذا الميناء اليوم.</p>
    <?php else: ?>
    <table>
        <thead>
        <tr>
            <th>الموظف</th><th>المناوبة</th><th>حالة الحضور</th>
            <th>المهمة الحالية</th><th>عدد الرحلات اليوم</th><th>الحالة</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($employeeRows as $row): ?>
            <?php
                $present = ($row['attendance_status'] ?? null) === 'present';
                $busy = $present && !empty($row['current_trip']);
                $statusLabel = !$present ? 'غير متاح' : ($busy ? 'مشغول' : 'متاح');
                $statusBadge = !$present ? 'badge-muted' : ($busy ? 'badge-warning' : 'badge-success');
            ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['shift_name']) ?></td>
                <td><?= e($row['attendance_status'] ?? 'لم يبدأ') ?></td>
                <td><?= $row['current_trip'] ? e($row['current_trip']) : '—' ?></td>
                <td class="num"><?= numberAr($row['trips_count']) ?></td>
                <td><span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="grid-2">
    <!-- القوارب المتوقعة -->
    <div class="panel">
        <h3>القوارب المتوقع وصولها</h3>
        <?php if (empty($expectedTrips)): ?>
            <p class="panel-hint">لا توجد قوارب متوقعة حاليًا.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الرحلة</th><th>القارب</th><th>الكابتن</th><th>الوصول المتوقع</th></tr></thead>
            <tbody>
            <?php foreach ($expectedTrips as $t): ?>
                <tr>
                    <td><?= e($t['trip_code']) ?></td>
                    <td><?= e($t['boat_name']) ?></td>
                    <td><?= e($t['captain_name']) ?></td>
                    <td><?= e($t['expected_arrival']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- القوارب الواصلة / تحت الإحصاء -->
    <div class="panel">
        <h3>القوارب الواصلة</h3>
        <?php if (empty($arrivedTrips)): ?>
            <p class="panel-hint">لا توجد قوارب واصلة حاليًا.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الرحلة</th><th>القارب</th><th>الحالة</th><th>الموظف</th><th>إجراء</th></tr></thead>
            <tbody>
            <?php foreach ($arrivedTrips as $t): ?>
                <tr>
                    <td><?= e($t['trip_code']) ?></td>
                    <td><?= e($t['boat_name']) ?></td>
                    <td>
                        <?php
                        $stLabel = match($t['status']) {
                            'arrived' => 'بانتظار موظف', 'waiting_employee' => 'بانتظار موظف',
                            'counting' => 'تحت الإحصاء', default => $t['status'],
                        };
                        ?>
                        <span class="badge badge-info"><?= e($stLabel) ?></span>
                    </td>
                    <td><?= $t['employee_name'] ? e($t['employee_name']) : '—' ?></td>
                    <td>
                        <?php if (in_array($t['status'], ['arrived','waiting_employee'], true)): ?>
                            <form method="post" style="display:flex; gap:6px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="assign_trip">
                                <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                                <select name="employee_id" required style="padding:5px; border-radius:6px; border:1px solid var(--line); font-size:12px;">
                                    <option value="">اختر موظف متاح</option>
                                    <?php foreach ($availableEmployeesForPort as $emp): ?>
                                        <option value="<?= (int)$emp['employee_id'] ?>"><?= e($emp['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">إسناد وبدء الإحصاء</button>
                            </form>
                        <?php else: ?>
                            <form method="post" style="display:flex; gap:6px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="change_employee">
                                <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                                <select name="employee_id" required style="padding:5px; border-radius:6px; border:1px solid var(--line); font-size:12px;">
                                    <option value="">تغيير الموظف</option>
                                    <?php foreach ($employeeRows as $emp): ?>
                                        <option value="<?= (int)$emp['employee_id'] ?>"><?= e($emp['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-outline btn-sm">تغيير</button>
                            </form>
                            <form method="post" style="margin-top:6px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="transfer_to_review">
                                <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm">تحويل للمراجعة</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- بانتظار مراجعة الفروقات -->
<div class="panel">
    <h3>رحلات بانتظار اعتماد فرق كبير</h3>
    <?php if (empty($reviewTrips)): ?>
        <p class="panel-hint">لا توجد رحلات بانتظار الاعتماد حاليًا. ✅</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الرحلة</th><th>القارب</th><th>الفرق (كجم)</th><th>نسبة الفرق</th><th>التصنيف</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($reviewTrips as $t): ?>
            <tr>
                <td><?= e($t['trip_code']) ?></td>
                <td><?= e($t['boat_name']) ?></td>
                <td class="num"><?= numberAr($t['diff_kg']) ?></td>
                <td class="num"><?= numberAr($t['diff_percent'], 1) ?>%</td>
                <td><span class="badge <?= severityBadgeClass($t['severity']) ?>"><?= severityLabel($t['severity']) ?></span></td>
                <td>
                    <form method="post" onsubmit="return confirm('تأكيد اعتماد هذا الفرق؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="approve_large_diff">
                        <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
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

<style>.hidden-form.open-form{ display:flex !important; }</style>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
