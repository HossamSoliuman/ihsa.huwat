<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'hr_manager', 'port_supervisor']);
$pageTitle   = 'الحضور والمناوبات';
$activeRoute = 'attendance.php';

$pdo = db();
$role = $currentUserData['role_code'];

$targetDate = $_GET['date'] ?? date('Y-m-d');
$portFilter = isset($_GET['port_id']) ? (int)$_GET['port_id'] : 0;

if ($role === 'port_supervisor') {
    $portFilter = (int)$currentUserData['port_id'];
}

$portsForFilter = [];
if ($role !== 'port_supervisor') {
    $portsForFilter = $pdo->query("SELECT id, name FROM ports WHERE is_active = 1 ORDER BY name")->fetchAll();
}

$redirectUrl = BASE_URL . '/dashboard/attendance.php?date=' . urlencode($targetDate)
    . ($portFilter ? '&port_id=' . $portFilter : '');

/* ------------------------------------------------------------
   الإجراءات
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'check_in') {
            $employeeId = (int)$_POST['employee_id'];
            $shiftId    = (int)$_POST['shift_id'];

            $stmt = $pdo->prepare(
                "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? AND shift_id = ?"
            );
            $stmt->execute([$employeeId, $targetDate, $shiftId]);
            $existing = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT start_time FROM shifts WHERE id = ?");
            $stmt->execute([$shiftId]);
            $shiftStart = $stmt->fetchColumn();
            $isLate = strtotime(date('H:i:s')) > strtotime($shiftStart) + 900; // سماح 15 دقيقة

            if ($existing) {
                $pdo->prepare("UPDATE attendance SET check_in = NOW(), status = ? WHERE id = ?")
                    ->execute([$isLate ? 'late' : 'present', $existing]);
            } else {
                $pdo->prepare(
                    "INSERT INTO attendance (employee_id, attendance_date, shift_id, check_in, status)
                     VALUES (?, ?, ?, NOW(), ?)"
                )->execute([$employeeId, $targetDate, $shiftId, $isLate ? 'late' : 'present']);
            }
            redirectWithMessage($redirectUrl, 'success', 'تم تسجيل الحضور.');

        } elseif ($action === 'check_out') {
            $attendanceId = (int)$_POST['attendance_id'];
            $pdo->prepare("UPDATE attendance SET check_out = NOW() WHERE id = ?")->execute([$attendanceId]);
            redirectWithMessage($redirectUrl, 'success', 'تم تسجيل الانصراف.');

        } elseif ($action === 'mark_absent') {
            $employeeId = (int)$_POST['employee_id'];
            $shiftId    = (int)$_POST['shift_id'];
            $stmt = $pdo->prepare(
                "SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ? AND shift_id = ?"
            );
            $stmt->execute([$employeeId, $targetDate, $shiftId]);
            $existing = $stmt->fetchColumn();
            if ($existing) {
                $pdo->prepare("UPDATE attendance SET status = 'absent', check_in = NULL, check_out = NULL WHERE id = ?")
                    ->execute([$existing]);
            } else {
                $pdo->prepare(
                    "INSERT INTO attendance (employee_id, attendance_date, shift_id, status) VALUES (?, ?, ?, 'absent')"
                )->execute([$employeeId, $targetDate, $shiftId]);
            }
            redirectWithMessage($redirectUrl, 'success', 'تم تسجيل الموظف كغائب.');

        } elseif ($action === 'swap_shift') {
            $assignmentId = (int)$_POST['assignment_id'];
            $newShiftId   = (int)$_POST['new_shift_id'];
            $stmt = $pdo->prepare("SELECT port_id FROM employee_assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);
            $rowPort = $stmt->fetchColumn();
            if ($role === 'port_supervisor' && (int)$rowPort !== (int)$currentUserData['port_id']) {
                redirectWithMessage($redirectUrl, 'error', 'لا تملك صلاحية تعديل هذه المناوبة.');
            }
            $pdo->prepare("UPDATE employee_assignments SET shift_id = ? WHERE id = ?")
                ->execute([$newShiftId, $assignmentId]);
            redirectWithMessage($redirectUrl, 'success', 'تم تبديل المناوبة بنجاح.');

        } elseif ($action === 'assign_substitute') {
            $portIdForSub = $role === 'port_supervisor' ? (int)$currentUserData['port_id'] : (int)$_POST['port_id'];
            $employeeId   = (int)$_POST['employee_id'];
            $shiftId      = (int)$_POST['shift_id'];
            $pdo->prepare(
                "INSERT INTO employee_assignments (employee_id, port_id, shift_id, assignment_date, is_temporary)
                 VALUES (?, ?, ?, ?, 1)"
            )->execute([$employeeId, $portIdForSub, $shiftId, $targetDate]);
            redirectWithMessage($redirectUrl, 'success', 'تم تعيين الموظف البديل.');
        }
    } catch (Throwable $e) {
        error_log('Attendance dashboard error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'حدث خطأ أثناء تنفيذ الإجراء.');
    }
}

/* ------------------------------------------------------------
   الاستعلامات
------------------------------------------------------------ */
$portScopeSql = '';
$portScopeParams = [];
if ($portFilter > 0) {
    $portScopeSql = " AND ea.port_id = ? ";
    $portScopeParams[] = $portFilter;
}

$shiftsList = $pdo->query("SELECT * FROM shifts ORDER BY start_time")->fetchAll();

// جدول المناوبات اليومية + الحضور
$sql = "SELECT ea.id AS assignment_id, ea.employee_id, ea.shift_id, ea.is_temporary,
               u.full_name, s.name AS shift_name, p.name AS port_name, p.id AS port_id,
               a.id AS attendance_id, a.check_in, a.check_out, a.status AS attendance_status
        FROM employee_assignments ea
        JOIN employees e ON e.id = ea.employee_id
        JOIN users u ON u.id = e.user_id
        JOIN shifts s ON s.id = ea.shift_id
        JOIN ports p ON p.id = ea.port_id
        LEFT JOIN attendance a ON a.employee_id = ea.employee_id AND a.attendance_date = ea.assignment_date AND a.shift_id = ea.shift_id
        WHERE ea.assignment_date = ?
        $portScopeSql
        ORDER BY s.start_time, u.full_name";
$params = array_merge([$targetDate], $portScopeParams);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// المؤشرات
$kpi = ['present' => 0, 'absent' => 0, 'late' => 0, 'on_leave' => 0, 'morning' => 0, 'evening' => 0, 'night' => 0, 'overtime_hours' => 0];
foreach ($assignments as $row) {
    $st = $row['attendance_status'];
    if ($st === 'present') $kpi['present']++;
    elseif ($st === 'absent') $kpi['absent']++;
    elseif ($st === 'late') { $kpi['late']++; $kpi['present']++; }

    if ($row['shift_name'] === 'morning') $kpi['morning']++;
    elseif ($row['shift_name'] === 'evening') $kpi['evening']++;
    elseif ($row['shift_name'] === 'night') $kpi['night']++;

    if ($row['check_in'] && $row['check_out']) {
        $shiftInfo = null;
        foreach ($shiftsList as $s) if ($s['id'] == $row['shift_id']) $shiftInfo = $s;
        if ($shiftInfo) {
            $shiftMinutes = (strtotime($shiftInfo['end_time']) - strtotime($shiftInfo['start_time'])) / 60;
            if ($shiftMinutes < 0) $shiftMinutes += 24 * 60;
            $workedMinutes = (strtotime($row['check_out']) - strtotime($row['check_in'])) / 60;
            $extra = $workedMinutes - $shiftMinutes;
            if ($extra > 0) $kpi['overtime_hours'] += $extra / 60;
        }
    }
}

// في إجازة اليوم (نطاق الميناء يصعب تحديده مباشرة لأن leaves غير مرتبطة بميناء، فنعرضها على مستوى الموظفين المسندين ضمن النطاق)
$sql = "SELECT COUNT(DISTINCT l.employee_id) FROM leaves l
        WHERE l.status = 'approved' AND ? BETWEEN l.start_date AND l.end_date";
$stmt = $pdo->prepare($sql);
$stmt->execute([$targetDate]);
$kpi['on_leave'] = (int)$stmt->fetchColumn();

// حالات التأخير التفصيلية
$lateRows = array_filter($assignments, fn($r) => $r['attendance_status'] === 'late');

// الموظفون البدلاء اليوم
$substituteRows = array_filter($assignments, fn($r) => (int)$r['is_temporary'] === 1);

// الموظفون غير المغطين: موانئ ضمن النطاق بدون أي حضور "present/late" في مناوبة معينة
$coverageGaps = [];
$portsToCheck = $portFilter > 0
    ? [$portFilter]
    : array_column($pdo->query("SELECT id FROM ports WHERE is_active = 1")->fetchAll(), 'id');

foreach ($portsToCheck as $pid) {
    foreach ($shiftsList as $s) {
        $covered = false;
        foreach ($assignments as $row) {
            if ((int)$row['port_id'] === (int)$pid && (int)$row['shift_id'] === (int)$s['id']
                && in_array($row['attendance_status'], ['present', 'late'], true)) {
                $covered = true;
                break;
            }
        }
        if (!$covered) {
            $coverageGaps[] = ['port_id' => $pid, 'shift_name' => $s['name']];
        }
    }
}

// قائمة الموظفين النشطين لاستخدامها بنماذج الإسناد
$allEmployees = $pdo->query("SELECT e.id, u.full_name FROM employees e JOIN users u ON u.id = e.user_id WHERE e.status = 'active' ORDER BY u.full_name")->fetchAll();

// أسماء الموانئ (لعرض اسم الميناء في فجوات التغطية)
$portNames = [];
foreach ($pdo->query("SELECT id, name FROM ports") as $p) { $portNames[$p['id']] = $p['name']; }

require __DIR__ . '/../../includes/header.php';
?>

<form method="get" class="panel" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; padding:16px 20px;">
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">التاريخ</label>
        <input type="date" name="date" value="<?= e($targetDate) ?>" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
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
    <button type="submit" class="btn btn-primary">تطبيق</button>
</form>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">الحاضرون</span><span class="stat-value"><?= numberAr($kpi['present']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">الغائبون</span><span class="stat-value"><?= numberAr($kpi['absent']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">المتأخرون</span><span class="stat-value"><?= numberAr($kpi['late']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">في إجازة</span><span class="stat-value"><?= numberAr($kpi['on_leave']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">المناوبة الصباحية</span><span class="stat-value"><?= numberAr($kpi['morning']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">المناوبة المسائية</span><span class="stat-value"><?= numberAr($kpi['evening']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">المناوبة الليلية</span><span class="stat-value"><?= numberAr($kpi['night']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">ساعات العمل الإضافي</span><span class="stat-value"><?= numberAr($kpi['overtime_hours'], 1) ?></span></div>
</div>

<!-- جدول الحضور والانصراف + المناوبات اليومية -->
<div class="panel">
    <h3>جدول الحضور والمناوبات اليومية</h3>
    <?php if (empty($assignments)): ?>
        <p class="panel-hint">لا يوجد موظفون مسندون بهذا التاريخ ضمن النطاق المحدد.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
        <tr>
            <th>الموظف</th><th>الميناء</th><th>المناوبة</th><th>الحالة</th>
            <th>وقت الحضور</th><th>وقت الانصراف</th><th>بديل</th><th>إجراءات</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($assignments as $row): ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['port_name']) ?></td>
                <td><?= e($row['shift_name']) ?></td>
                <td>
                    <?php
                    $stLabel = match($row['attendance_status']) {
                        'present' => 'حاضر', 'late' => 'متأخر', 'absent' => 'غائب', default => 'لم يبدأ',
                    };
                    $stBadge = match($row['attendance_status']) {
                        'present' => 'badge-success', 'late' => 'badge-warning', 'absent' => 'badge-danger', default => 'badge-muted',
                    };
                    ?>
                    <span class="badge <?= $stBadge ?>"><?= e($stLabel) ?></span>
                </td>
                <td><?= $row['check_in'] ? e($row['check_in']) : '—' ?></td>
                <td><?= $row['check_out'] ? e($row['check_out']) : '—' ?></td>
                <td><?= $row['is_temporary'] ? '<span class="badge badge-info">بديل</span>' : '—' ?></td>
                <td>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <?php if (!$row['check_in']): ?>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="check_in">
                                <input type="hidden" name="employee_id" value="<?= (int)$row['employee_id'] ?>">
                                <input type="hidden" name="shift_id" value="<?= (int)$row['shift_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm">تسجيل حضور</button>
                            </form>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="mark_absent">
                                <input type="hidden" name="employee_id" value="<?= (int)$row['employee_id'] ?>">
                                <input type="hidden" name="shift_id" value="<?= (int)$row['shift_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm">غائب</button>
                            </form>
                        <?php elseif (!$row['check_out']): ?>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="check_out">
                                <input type="hidden" name="attendance_id" value="<?= (int)$row['attendance_id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm">تسجيل انصراف</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display:flex; gap:4px;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="swap_shift">
                            <input type="hidden" name="assignment_id" value="<?= (int)$row['assignment_id'] ?>">
                            <select name="new_shift_id" style="padding:4px; border-radius:6px; border:1px solid var(--line); font-size:11px;">
                                <?php foreach ($shiftsList as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= $s['id'] == $row['shift_id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">تبديل</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <!-- الموظفون غير المغطين -->
    <div class="panel">
        <h3>الموظفون غير المغطين (فجوات المناوبات)</h3>
        <?php if (empty($coverageGaps)): ?>
            <p class="panel-hint">لا توجد فجوات تغطية حاليًا. ✅</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الميناء</th><th>المناوبة</th><th>تعيين بديل</th></tr></thead>
            <tbody>
            <?php foreach ($coverageGaps as $gap): ?>
                <tr>
                    <td><?= e($portNames[$gap['port_id']] ?? '—') ?></td>
                    <td><?= e($gap['shift_name']) ?></td>
                    <td>
                        <form method="post" style="display:flex; gap:4px;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="assign_substitute">
                            <?php if ($role !== 'port_supervisor'): ?>
                                <input type="hidden" name="port_id" value="<?= (int)$gap['port_id'] ?>">
                            <?php endif; ?>
                            <?php foreach ($shiftsList as $s): if ($s['name'] === $gap['shift_name']): ?>
                                <input type="hidden" name="shift_id" value="<?= (int)$s['id'] ?>">
                            <?php endif; endforeach; ?>
                            <select name="employee_id" required style="padding:4px; border-radius:6px; border:1px solid var(--line); font-size:11px;">
                                <option value="">اختر موظف بديل</option>
                                <?php foreach ($allEmployees as $emp): ?>
                                    <option value="<?= (int)$emp['id'] ?>"><?= e($emp['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">تعيين</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- الموظفون البدلاء اليوم -->
    <div class="panel">
        <h3>الموظفون البدلاء اليوم</h3>
        <?php if (empty($substituteRows)): ?>
            <p class="panel-hint">لا يوجد موظفون بدلاء بهذا التاريخ.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الموظف</th><th>الميناء</th><th>المناوبة</th></tr></thead>
            <tbody>
            <?php foreach ($substituteRows as $row): ?>
                <tr><td><?= e($row['full_name']) ?></td><td><?= e($row['port_name']) ?></td><td><?= e($row['shift_name']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- حالات التأخير -->
<div class="panel">
    <h3>حالات التأخير</h3>
    <?php if (empty($lateRows)): ?>
        <p class="panel-hint">لا توجد حالات تأخير مسجّلة بهذا التاريخ. ✅</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الموظف</th><th>الميناء</th><th>المناوبة</th><th>وقت الحضور</th></tr></thead>
        <tbody>
        <?php foreach ($lateRows as $row): ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['port_name']) ?></td>
                <td><?= e($row['shift_name']) ?></td>
                <td><?= e($row['check_in']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
