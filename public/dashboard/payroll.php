<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'finance_officer', 'hr_manager']);
$pageTitle   = 'الرواتب والمستحقات';
$activeRoute = 'payroll.php';

$pdo = db();

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = max(1, min(12, $month));

$redirectUrl = BASE_URL . "/dashboard/payroll.php?month=$month&year=$year";

/* ------------------------------------------------------------
   الإجراءات
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'generate_payroll') {
            $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
            $employeesActive = $pdo->query("SELECT id, base_salary FROM employees WHERE status != 'terminated'")->fetchAll();

            foreach ($employeesActive as $emp) {
                $exists = $pdo->prepare("SELECT id FROM payroll WHERE employee_id = ? AND period_month = ? AND period_year = ?");
                $exists->execute([$emp['id'], $month, $year]);
                if ($exists->fetchColumn()) continue; // موجود مسبقًا، لا نكرر التوليد

                $base = (float)$emp['base_salary'];
                $dailyRate  = $base > 0 ? $base / 30 : 0;
                $hourlyRate = $base > 0 ? $base / 240 : 0;

                // ساعات العمل الإضافي خلال الشهر (من فروقات الحضور/الانصراف عن نهاية المناوبة)
                $stmt = $pdo->prepare(
                    "SELECT a.check_in, a.check_out, s.start_time, s.end_time
                     FROM attendance a JOIN shifts s ON s.id = a.shift_id
                     WHERE a.employee_id = ? AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
                       AND a.check_in IS NOT NULL AND a.check_out IS NOT NULL"
                );
                $stmt->execute([$emp['id'], $month, $year]);
                $overtimeHours = 0.0;
                foreach ($stmt->fetchAll() as $att) {
                    $shiftMin = (strtotime($att['end_time']) - strtotime($att['start_time'])) / 60;
                    if ($shiftMin < 0) $shiftMin += 24 * 60;
                    $workedMin = (strtotime($att['check_out']) - strtotime($att['check_in'])) / 60;
                    $extra = $workedMin - $shiftMin;
                    if ($extra > 0) $overtimeHours += $extra / 60;
                }
                $overtimeAmount = round($overtimeHours * $hourlyRate * 1.5, 2); // 1.5x للوقت الإضافي

                // عدد أيام الغياب خلال الشهر
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND status = 'absent'
                     AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?"
                );
                $stmt->execute([$emp['id'], $month, $year]);
                $absentDays = (int)$stmt->fetchColumn();
                $deductions = round($absentDays * $dailyRate, 2);

                $netSalary = round($base + $overtimeAmount - $deductions, 2);

                $pdo->prepare(
                    "INSERT INTO payroll (employee_id, period_month, period_year, base_salary, allowances,
                        overtime_hours, overtime_amount, bonuses, deductions, net_salary, paid_status)
                     VALUES (?, ?, ?, ?, 0, ?, ?, 0, ?, ?, 'pending')"
                )->execute([$emp['id'], $month, $year, $base, round($overtimeHours, 2), $overtimeAmount, $deductions, $netSalary]);
            }
            redirectWithMessage($redirectUrl, 'success', 'تم توليد مسير رواتب الشهر بنجاح.');

        } elseif ($action === 'update_row') {
            $payrollId  = (int)$_POST['payroll_id'];
            $allowances = (float)$_POST['allowances'];
            $bonuses    = (float)$_POST['bonuses'];
            $deductions = (float)$_POST['deductions'];

            $stmt = $pdo->prepare("SELECT base_salary, overtime_amount FROM payroll WHERE id = ?");
            $stmt->execute([$payrollId]);
            $row = $stmt->fetch();
            $net = round((float)$row['base_salary'] + $allowances + (float)$row['overtime_amount'] + $bonuses - $deductions, 2);

            $pdo->prepare(
                "UPDATE payroll SET allowances = ?, bonuses = ?, deductions = ?, net_salary = ? WHERE id = ?"
            )->execute([$allowances, $bonuses, $deductions, $net, $payrollId]);
            redirectWithMessage($redirectUrl, 'success', 'تم تحديث الراتب بنجاح.');

        } elseif ($action === 'mark_paid') {
            $payrollId = (int)$_POST['payroll_id'];
            $pdo->prepare("UPDATE payroll SET paid_status = 'paid', paid_at = NOW() WHERE id = ?")
                ->execute([$payrollId]);
            redirectWithMessage($redirectUrl, 'success', 'تم اعتماد صرف الراتب.');
        }
    } catch (Throwable $e) {
        error_log('Payroll dashboard error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'حدث خطأ أثناء تنفيذ الإجراء.');
    }
}

/* ------------------------------------------------------------
   المؤشرات
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS c, COALESCE(SUM(base_salary),0) AS s_base, COALESCE(SUM(allowances),0) AS s_allow,
            COALESCE(SUM(overtime_hours),0) AS s_ot_hours, COALESCE(SUM(overtime_amount),0) AS s_ot_amount,
            COALESCE(SUM(bonuses),0) AS s_bonus, COALESCE(SUM(deductions),0) AS s_deduct,
            COALESCE(SUM(net_salary),0) AS s_net, SUM(paid_status = 'paid') AS c_paid
     FROM payroll WHERE period_month = ? AND period_year = ?"
);
$stmt->execute([$month, $year]);
$kpi = $stmt->fetch();

/* ------------------------------------------------------------
   مسير الرواتب الشهري
------------------------------------------------------------ */
$stmt = $pdo->prepare(
    "SELECT pr.*, u.full_name FROM payroll pr
     JOIN employees e ON e.id = pr.employee_id
     JOIN users u ON u.id = e.user_id
     WHERE pr.period_month = ? AND pr.period_year = ?
     ORDER BY u.full_name"
);
$stmt->execute([$month, $year]);
$payrollRows = $stmt->fetchAll();

/* ------------------------------------------------------------
   المقارنة بين الأشهر (آخر 6 فترات مسجلة)
------------------------------------------------------------ */
$monthlyComparison = $pdo->query(
    "SELECT period_month, period_year, SUM(net_salary) AS total_net
     FROM payroll GROUP BY period_year, period_month
     ORDER BY period_year DESC, period_month DESC LIMIT 6"
)->fetchAll();

$monthNames = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];

require __DIR__ . '/../../includes/header.php';
?>

<form method="get" class="panel" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; padding:16px 20px;">
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">الشهر</label>
        <select name="month" style="padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
            <?php foreach ($monthNames as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num == $month ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">السنة</label>
        <input type="number" name="year" value="<?= (int)$year ?>" style="width:100px; padding:7px 10px; border-radius:8px; border:1px solid var(--line);">
    </div>
    <button type="submit" class="btn btn-outline">عرض</button>

    <?php if (empty($payrollRows)): ?>
    <form method="post" style="margin-inline-start:auto;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="generate_payroll">
        <button type="submit" class="btn btn-primary">توليد مسير رواتب هذا الشهر</button>
    </form>
    <?php endif; ?>
</form>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">إجمالي الرواتب (صافي)</span><span class="stat-value"><?= numberAr($kpi['s_net']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">الرواتب الأساسية</span><span class="stat-value"><?= numberAr($kpi['s_base']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">البدلات</span><span class="stat-value"><?= numberAr($kpi['s_allow']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">العمل الإضافي (ساعة / مبلغ)</span><span class="stat-value"><?= numberAr($kpi['s_ot_hours'],1) ?> / <?= numberAr($kpi['s_ot_amount']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">المكافآت</span><span class="stat-value"><?= numberAr($kpi['s_bonus']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">الخصومات</span><span class="stat-value"><?= numberAr($kpi['s_deduct']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">صافي الرواتب</span><span class="stat-value"><?= numberAr($kpi['s_net']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">تم صرفها</span><span class="stat-value"><?= numberAr($kpi['c_paid']) ?> / <?= numberAr($kpi['c']) ?></span></div>
</div>

<!-- مسير الرواتب -->
<div class="panel">
    <h3>مسير رواتب <?= e($monthNames[$month]) ?> <?= (int)$year ?></h3>
    <?php if (empty($payrollRows)): ?>
        <p class="panel-hint">لا يوجد مسير رواتب مولَّد لهذا الشهر بعد. استخدم زر "توليد مسير رواتب هذا الشهر" أعلاه.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
        <tr>
            <th>الموظف</th><th>الأساسي</th><th>إضافي (س/مبلغ)</th>
            <th>بدلات / مكافآت / خصومات</th><th>الصافي</th><th>الحالة</th><th>إجراء</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($payrollRows as $row): $isPaid = $row['paid_status'] === 'paid'; ?>
            <tr>
                <td><?= e($row['full_name']) ?></td>
                <td class="num"><?= numberAr($row['base_salary']) ?></td>
                <td class="num"><?= numberAr($row['overtime_hours'],1) ?> / <?= numberAr($row['overtime_amount']) ?></td>
                <td>
                    <form method="post" style="display:flex; gap:4px; align-items:center; flex-wrap:wrap;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_row">
                        <input type="hidden" name="payroll_id" value="<?= (int)$row['id'] ?>">
                        <input type="number" step="0.01" name="allowances" value="<?= e($row['allowances']) ?>" placeholder="بدلات"
                               style="width:70px; padding:4px; border:1px solid var(--line); border-radius:6px; font-size:11px;" <?= $isPaid ? 'disabled' : '' ?>>
                        <input type="number" step="0.01" name="bonuses" value="<?= e($row['bonuses']) ?>" placeholder="مكافآت"
                               style="width:70px; padding:4px; border:1px solid var(--line); border-radius:6px; font-size:11px;" <?= $isPaid ? 'disabled' : '' ?>>
                        <input type="number" step="0.01" name="deductions" value="<?= e($row['deductions']) ?>" placeholder="خصومات"
                               style="width:70px; padding:4px; border:1px solid var(--line); border-radius:6px; font-size:11px;" <?= $isPaid ? 'disabled' : '' ?>>
                        <?php if (!$isPaid): ?>
                            <button type="submit" class="btn btn-outline btn-sm">حفظ</button>
                        <?php endif; ?>
                    </form>
                </td>
                <td class="num"><strong><?= numberAr($row['net_salary']) ?></strong></td>
                <td>
                    <span class="badge <?= $isPaid ? 'badge-success' : 'badge-warning' ?>">
                        <?= $isPaid ? 'مصروف' : 'معلّق' ?>
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <?php if (!$isPaid): ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="mark_paid">
                        <input type="hidden" name="payroll_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('تأكيد صرف الراتب؟');">صرف</button>
                    </form>
                    <?php else: ?>
                        <span style="font-size:11px; color:var(--muted);">بتاريخ <?= e($row['paid_at']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- المقارنة بين الأشهر -->
<div class="panel">
    <h3>المقارنة بين الأشهر (آخر 6 فترات)</h3>
    <?php if (empty($monthlyComparison)): ?>
        <p class="panel-hint">لا توجد بيانات كافية بعد.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>الشهر</th><th>إجمالي الصافي</th></tr></thead>
        <tbody>
        <?php foreach ($monthlyComparison as $m): ?>
            <tr>
                <td><?= e($monthNames[(int)$m['period_month']]) ?> <?= (int)$m['period_year'] ?></td>
                <td class="num"><?= numberAr($m['total_net']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
