<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'hr_manager']);
$pageTitle   = 'إدارة موظفي الإحصاء / الموارد البشرية';
$activeRoute = 'hr.php';

$pdo = db();
$redirectUrl = BASE_URL . '/dashboard/hr.php';

/* ------------------------------------------------------------
   الإجراءات
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'approve_leave') {
            $leaveId = (int)$_POST['leave_id'];
            $pdo->prepare("UPDATE leaves SET status = 'approved', approved_by = ? WHERE id = ?")
                ->execute([$currentUserData['id'], $leaveId]);
            $stmt = $pdo->prepare("SELECT employee_id FROM leaves WHERE id = ?");
            $stmt->execute([$leaveId]);
            $empId = $stmt->fetchColumn();
            if ($empId) {
                $pdo->prepare("UPDATE employees SET status = 'on_leave' WHERE id = ?")->execute([$empId]);
            }
            redirectWithMessage($redirectUrl, 'success', 'تم اعتماد طلب الإجازة.');

        } elseif ($action === 'reject_leave') {
            $leaveId = (int)$_POST['leave_id'];
            $pdo->prepare("UPDATE leaves SET status = 'rejected', approved_by = ? WHERE id = ?")
                ->execute([$currentUserData['id'], $leaveId]);
            redirectWithMessage($redirectUrl, 'success', 'تم رفض طلب الإجازة.');

        } elseif ($action === 'assign_employee') {
            $employeeId = (int)$_POST['employee_id'];
            $portId     = (int)$_POST['port_id'];
            $shiftId    = (int)$_POST['shift_id'];
            $date       = $_POST['assignment_date'] ?? date('Y-m-d');
            $pdo->prepare(
                "INSERT INTO employee_assignments (employee_id, port_id, shift_id, assignment_date, is_temporary)
                 VALUES (?, ?, ?, ?, 0)
                 ON DUPLICATE KEY UPDATE port_id = VALUES(port_id), shift_id = VALUES(shift_id)"
            )->execute([$employeeId, $portId, $shiftId, $date]);
            redirectWithMessage($redirectUrl, 'success', 'تم تكليف/نقل الموظف بنجاح.');

        } elseif ($action === 'add_employee') {
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $hireDate = $_POST['hire_date'] ?? date('Y-m-d');
            $contractType = $_POST['contract_type'] ?? 'permanent';
            $contractEnd  = $_POST['contract_end_date'] ?: null;

            if ($fullName === '' || $username === '' || strlen($password) < 8) {
                redirectWithMessage($redirectUrl, 'error', 'تحقق من البيانات: كلمة المرور 8 أحرف على الأقل.');
            }

            $roleId = $pdo->query("SELECT id FROM roles WHERE code = 'stat_employee'")->fetchColumn();

            $pdo->beginTransaction();
            $pdo->prepare(
                "INSERT INTO users (role_id, full_name, username, password_hash, is_active) VALUES (?, ?, ?, ?, 1)"
            )->execute([$roleId, $fullName, $username, password_hash($password, PASSWORD_DEFAULT)]);
            $newUserId = $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO employees (user_id, hire_date, contract_type, contract_end_date, status)
                 VALUES (?, ?, ?, ?, 'active')"
            )->execute([$newUserId, $hireDate, $contractType, $contractEnd]);
            $pdo->commit();

            redirectWithMessage($redirectUrl, 'success', 'تمت إضافة الموظف الجديد بنجاح.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('HR dashboard error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'حدث خطأ أثناء تنفيذ الإجراء. تأكد أن اسم المستخدم غير مستخدم مسبقًا.');
    }
}

/* ------------------------------------------------------------
   المؤشرات
------------------------------------------------------------ */
$kpi = [
    'total'      => (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(),
    'active'     => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn(),
    'permanent'  => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE contract_type = 'permanent'")->fetchColumn(),
    'temporary'  => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE contract_type = 'temporary'")->fetchColumn(),
    'expiring'   => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE contract_end_date IS NOT NULL AND contract_end_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)")->fetchColumn(),
    'on_leave'   => (int)$pdo->query("SELECT COUNT(DISTINCT employee_id) FROM leaves WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date")->fetchColumn(),
    'pending_leaves' => (int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn(),
    'new_this_month' => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE MONTH(hire_date) = MONTH(CURDATE()) AND YEAR(hire_date) = YEAR(CURDATE())")->fetchColumn(),
];

/* ------------------------------------------------------------
   قائمة الموظفين
------------------------------------------------------------ */
$employees = $pdo->query(
    "SELECT e.id, u.full_name, u.username, e.hire_date, e.contract_type, e.contract_end_date, e.status,
            (SELECT p.name FROM employee_assignments ea JOIN ports p ON p.id = ea.port_id
                WHERE ea.employee_id = e.id AND ea.assignment_date = CURDATE() ORDER BY ea.id DESC LIMIT 1) AS today_port
     FROM employees e JOIN users u ON u.id = e.user_id
     ORDER BY u.full_name"
)->fetchAll();

/* ------------------------------------------------------------
   التعيين حسب المنطقة والمحافظة والميناء (تكليفات اليوم)
------------------------------------------------------------ */
$byGeo = $pdo->query(
    "SELECT r.name AS region_name, g.name AS gov_name, p.name AS port_name, COUNT(DISTINCT ea.employee_id) AS employees_count
     FROM employee_assignments ea
     JOIN ports p ON p.id = ea.port_id
     JOIN governorates g ON g.id = p.governorate_id
     JOIN regions r ON r.id = g.region_id
     WHERE ea.assignment_date = CURDATE()
     GROUP BY r.id, g.id, p.id
     ORDER BY r.name, g.name, p.name"
)->fetchAll();

/* ------------------------------------------------------------
   العقود القريبة من الانتهاء
------------------------------------------------------------ */
$expiringContracts = $pdo->query(
    "SELECT e.id, u.full_name, e.contract_end_date, DATEDIFF(e.contract_end_date, CURDATE()) AS days_left
     FROM employees e JOIN users u ON u.id = e.user_id
     WHERE e.contract_end_date IS NOT NULL AND e.contract_end_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)
     ORDER BY e.contract_end_date"
)->fetchAll();

/* ------------------------------------------------------------
   طلبات الإجازة المعلقة
------------------------------------------------------------ */
$pendingLeaves = $pdo->query(
    "SELECT l.id, u.full_name, l.start_date, l.end_date, l.reason
     FROM leaves l JOIN employees e ON e.id = l.employee_id JOIN users u ON u.id = e.user_id
     WHERE l.status = 'pending' ORDER BY l.created_at"
)->fetchAll();

// بيانات لنماذج التكليف
$allPorts = $pdo->query("SELECT id, name FROM ports WHERE is_active = 1 ORDER BY name")->fetchAll();
$allShifts = $pdo->query("SELECT * FROM shifts ORDER BY start_time")->fetchAll();
$activeEmployeesList = $pdo->query("SELECT e.id, u.full_name FROM employees e JOIN users u ON u.id = e.user_id WHERE e.status IN ('active','on_leave') ORDER BY u.full_name")->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">إجمالي موظفي الإحصاء</span><span class="stat-value"><?= numberAr($kpi['total']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون النشطون</span><span class="stat-value"><?= numberAr($kpi['active']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">العقود الدائمة</span><span class="stat-value"><?= numberAr($kpi['permanent']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">العقود المؤقتة</span><span class="stat-value"><?= numberAr($kpi['temporary']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">عقود قريبة من الانتهاء</span><span class="stat-value"><?= numberAr($kpi['expiring']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الموظفون في إجازة</span><span class="stat-value"><?= numberAr($kpi['on_leave']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">طلبات إجازة معلقة</span><span class="stat-value"><?= numberAr($kpi['pending_leaves']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">موظفون جدد هذا الشهر</span><span class="stat-value"><?= numberAr($kpi['new_this_month']) ?></span></div>
</div>

<!-- قائمة الموظفين -->
<div class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>قائمة الموظفين</h3>
        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('addEmpForm').classList.toggle('open-form')">+ إضافة موظف جديد</button>
    </div>

    <form method="post" id="addEmpForm" class="hidden-form" style="display:none; gap:10px; flex-wrap:wrap; margin-bottom:16px; padding:14px; background:#FAF8F2; border-radius:8px;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_employee">
        <input type="text" name="full_name" placeholder="الاسم الكامل" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
        <input type="text" name="username" placeholder="اسم المستخدم" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
        <input type="password" name="password" placeholder="كلمة المرور (8 أحرف+)" required minlength="8" style="padding:8px; border-radius:8px; border:1px solid var(--line);">
        <input type="date" name="hire_date" value="<?= date('Y-m-d') ?>" style="padding:8px; border-radius:8px; border:1px solid var(--line);">
        <select name="contract_type" style="padding:8px; border-radius:8px; border:1px solid var(--line);">
            <option value="permanent">عقد دائم</option>
            <option value="temporary">عقد مؤقت</option>
        </select>
        <input type="date" name="contract_end_date" placeholder="نهاية العقد (إن وجد)" style="padding:8px; border-radius:8px; border:1px solid var(--line);">
        <button type="submit" class="btn btn-primary btn-sm">حفظ</button>
    </form>

    <div style="overflow-x:auto;">
    <table>
        <thead><tr><th>الاسم</th><th>اسم المستخدم</th><th>تاريخ التعيين</th><th>نوع العقد</th><th>نهاية العقد</th><th>الحالة</th><th>ميناء اليوم</th></tr></thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?= e($emp['full_name']) ?></td>
                <td><?= e($emp['username']) ?></td>
                <td><?= e($emp['hire_date']) ?></td>
                <td><?= $emp['contract_type'] === 'permanent' ? 'دائم' : 'مؤقت' ?></td>
                <td><?= $emp['contract_end_date'] ? e($emp['contract_end_date']) : '—' ?></td>
                <td>
                    <?php
                    $stMap = ['active' => ['نشط','badge-success'], 'on_leave' => ['في إجازة','badge-info'], 'suspended' => ['موقوف','badge-warning'], 'terminated' => ['منتهي','badge-danger']];
                    [$lbl, $cls] = $stMap[$emp['status']] ?? ['—','badge-muted'];
                    ?>
                    <span class="badge <?= $cls ?>"><?= e($lbl) ?></span>
                </td>
                <td><?= $emp['today_port'] ? e($emp['today_port']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="grid-2">
    <!-- التعيين الجغرافي -->
    <div class="panel">
        <h3>التعيين حسب المنطقة والمحافظة والميناء (اليوم)</h3>
        <?php if (empty($byGeo)): ?>
            <p class="panel-hint">لا توجد تكليفات مسجّلة اليوم.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>المنطقة</th><th>المحافظة</th><th>الميناء</th><th>عدد الموظفين</th></tr></thead>
            <tbody>
            <?php foreach ($byGeo as $row): ?>
                <tr>
                    <td><?= e($row['region_name']) ?></td>
                    <td><?= e($row['gov_name']) ?></td>
                    <td><?= e($row['port_name']) ?></td>
                    <td class="num"><?= numberAr($row['employees_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- العقود القريبة من الانتهاء -->
    <div class="panel">
        <h3>العقود القريبة من الانتهاء (خلال 30 يومًا)</h3>
        <?php if (empty($expiringContracts)): ?>
            <p class="panel-hint">لا توجد عقود قريبة من الانتهاء. ✅</p>
        <?php else: ?>
        <table>
            <thead><tr><th>الموظف</th><th>تاريخ الانتهاء</th><th>الأيام المتبقية</th></tr></thead>
            <tbody>
            <?php foreach ($expiringContracts as $c): ?>
                <tr>
                    <td><?= e($c['full_name']) ?></td>
                    <td><?= e($c['contract_end_date']) ?></td>
                    <td><span class="badge <?= $c['days_left'] <= 7 ? 'badge-danger' : 'badge-warning' ?>"><?= numberAr($c['days_left']) ?> يوم</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- طلبات الإجازة -->
<div class="panel">
    <h3>طلبات الإجازة المعلقة</h3>
    <?php if (empty($pendingLeaves)): ?>
        <p class="panel-hint">لا توجد طلبات إجازة معلقة. ✅</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الموظف</th><th>من</th><th>إلى</th><th>السبب</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($pendingLeaves as $l): ?>
            <tr>
                <td><?= e($l['full_name']) ?></td>
                <td><?= e($l['start_date']) ?></td>
                <td><?= e($l['end_date']) ?></td>
                <td><?= $l['reason'] ? e($l['reason']) : '—' ?></td>
                <td style="display:flex; gap:6px;">
                    <form method="post"><?= csrfField() ?>
                        <input type="hidden" name="action" value="approve_leave">
                        <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">اعتماد</button>
                    </form>
                    <form method="post"><?= csrfField() ?>
                        <input type="hidden" name="action" value="reject_leave">
                        <input type="hidden" name="leave_id" value="<?= (int)$l['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">رفض</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- نقل وتكليف موظف -->
<div class="panel">
    <h3>نقل / تكليف موظف لميناء</h3>
    <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="assign_employee">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الموظف</label>
            <select name="employee_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
                <?php foreach ($activeEmployeesList as $emp): ?>
                    <option value="<?= (int)$emp['id'] ?>"><?= e($emp['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الميناء</label>
            <select name="port_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
                <?php foreach ($allPorts as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">المناوبة</label>
            <select name="shift_id" required style="padding:8px; border-radius:8px; border:1px solid var(--line);">
                <?php foreach ($allShifts as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">تاريخ البدء</label>
            <input type="date" name="assignment_date" value="<?= date('Y-m-d') ?>" style="padding:8px; border-radius:8px; border:1px solid var(--line);">
        </div>
        <button type="submit" class="btn btn-primary">تنفيذ التكليف</button>
    </form>
</div>

<style>.hidden-form.open-form{ display:flex !important; }</style>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
