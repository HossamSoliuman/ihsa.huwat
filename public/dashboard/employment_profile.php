<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['employee_portal']);
$pageTitle = 'ملفي الوظيفي';
$activeRoute = 'employment_profile.php';
$bodyClass = 'employment-profile-page';
$pageStyles = ['css/employment.css'];
$pageScripts = [];

$pdo = db();
$redirectUrl = BASE_URL . '/dashboard/employment_profile.php';

function employmentProfileDate(?string $value, bool $includeTime = false): string
{
    if (!$value) return '—';

    try {
        $date = new DateTimeImmutable($value);
        return $date->format($includeTime ? 'Y/m/d - H:i' : 'Y/m/d');
    } catch (Throwable $e) {
        return '—';
    }
}

function employmentProfileMoney($value): string
{
    return number_format((float)($value ?? 0), 2) . ' ر.س';
}

function employmentProfileStatusClass(string $status): string
{
    return match ($status) {
        'active', 'approved', 'accepted', 'account_created', 'paid', 'present' => 'badge-success',
        'on_leave', 'pending', 'under_review', 'shortlisted', 'interview', 'late' => 'badge-warning',
        'suspended', 'rejected', 'terminated', 'absent' => 'badge-danger',
        default => 'badge-muted',
    };
}

function employmentProfileExactDate(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

$profileStmt = $pdo->prepare(
    "SELECT
        u.id AS user_id,
        u.full_name AS account_full_name,
        u.email AS account_email,
        e.id AS employee_id,
        e.employment_application_id,
        e.employee_number,
        e.national_id,
        e.job_title,
        e.department AS employee_department,
        e.job_grade,
        e.supervisor_name,
        e.supervisor_phone,
        e.hire_date,
        e.contract_type,
        e.contract_end_date,
        e.base_salary,
        e.status AS employee_status,
        a.reference_no AS application_reference,
        a.status AS application_status,
        a.full_name AS application_full_name,
        a.nationality,
        a.identity_type,
        a.identity_number,
        a.birth_date,
        a.gender,
        a.marital_status,
        a.children_count,
        a.mobile,
        a.phone,
        a.email AS application_email,
        a.city AS applicant_city,
        a.address,
        a.work_type,
        a.source,
        a.education_level,
        a.specialization,
        a.institution,
        a.graduation_year,
        a.experience_years,
        a.current_employer,
        a.current_job_title,
        a.professional_summary,
        a.skills,
        a.availability_date,
        a.cover_letter,
        a.submitted_at,
        a.accepted_at,
        j.id AS job_id,
        j.reference_no AS job_reference,
        j.title_ar AS advertised_job_title,
        j.department AS job_department,
        j.employment_type AS advertised_employment_type,
        j.city AS job_city,
        preferred_port.name AS preferred_port_name,
        preferred_port.location_name AS preferred_port_location,
        job_port.name AS job_port_name,
        job_port.location_name AS job_port_location,
        COALESCE(preferred_port.name, job_port.name) AS preferred_or_job_port_name,
        COALESCE(preferred_port.location_name, job_port.location_name, j.city) AS preferred_or_job_location
     FROM users u
     LEFT JOIN employees e ON e.user_id = u.id
     LEFT JOIN employment_applications a ON a.id = e.employment_application_id
     LEFT JOIN employment_jobs j ON j.id = a.job_id
     LEFT JOIN ports preferred_port ON preferred_port.id = a.preferred_port_id
     LEFT JOIN ports job_port ON job_port.id = j.port_id
     WHERE u.id = ?
     LIMIT 1"
);
$profileStmt->execute([(int)$currentUserData['id']]);
$profile = $profileStmt->fetch() ?: [];
$employeeId = (int)($profile['employee_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }

    if (($_POST['action'] ?? '') !== 'request_leave') {
        redirectWithMessage($redirectUrl, 'error', 'الإجراء المطلوب غير صالح.');
    }

    if ($employeeId < 1) {
        redirectWithMessage($redirectUrl, 'error', 'لم يكتمل ربط حسابك بملف وظيفي بعد.');
    }

    $startDate = trim((string)($_POST['start_date'] ?? ''));
    $endDate = trim((string)($_POST['end_date'] ?? ''));
    $reason = trim((string)($_POST['reason'] ?? ''));
    $start = employmentProfileExactDate($startDate);
    $end = employmentProfileExactDate($endDate);
    $today = new DateTimeImmutable('today');

    if (!$start || !$end) {
        redirectWithMessage($redirectUrl, 'error', 'أدخل تاريخ بداية ونهاية صحيحين للإجازة.');
    }
    if ($start < $today) {
        redirectWithMessage($redirectUrl, 'error', 'لا يمكن تقديم طلب إجازة يبدأ قبل تاريخ اليوم.');
    }
    if ($end < $start) {
        redirectWithMessage($redirectUrl, 'error', 'تاريخ نهاية الإجازة يجب أن يساوي تاريخ البداية أو يأتي بعده.');
    }
    if (mb_strlen($reason) > 255) {
        redirectWithMessage($redirectUrl, 'error', 'سبب الإجازة يجب ألا يتجاوز 255 حرفًا.');
    }

    try {
        $overlapStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM leaves
             WHERE employee_id = ?
               AND status IN ('pending', 'approved')
               AND start_date <= ?
               AND end_date >= ?"
        );
        $overlapStmt->execute([$employeeId, $endDate, $startDate]);
        if ((int)$overlapStmt->fetchColumn() > 0) {
            redirectWithMessage($redirectUrl, 'error', 'يوجد طلب إجازة قائم يتداخل مع هذه الفترة.');
        }

        $insertStmt = $pdo->prepare(
            "INSERT INTO leaves (employee_id, start_date, end_date, reason, status)
             VALUES (?, ?, ?, ?, 'pending')"
        );
        $insertStmt->execute([$employeeId, $startDate, $endDate, $reason !== '' ? $reason : null]);
        redirectWithMessage($redirectUrl, 'success', 'تم إرسال طلب الإجازة إلى الموارد البشرية للمراجعة.');
    } catch (Throwable $e) {
        error_log('Employment profile leave request error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl, 'error', 'تعذر إرسال طلب الإجازة الآن. حاول مرة أخرى لاحقًا.');
    }
}

$attendanceSummary = [
    'recorded_days' => 0,
    'present_days' => 0,
    'late_days' => 0,
    'absent_days' => 0,
    'leave_days' => 0,
];
$recentAttendance = [];
$latestPayroll = null;
$leaveHistory = [];
$latestAssignment = null;

if ($employeeId > 0) {
    $attendanceStmt = $pdo->prepare(
        "SELECT
            COUNT(DISTINCT attendance_date) AS recorded_days,
            COUNT(DISTINCT CASE WHEN status IN ('present', 'late') THEN attendance_date END) AS present_days,
            COUNT(DISTINCT CASE WHEN status = 'late' THEN attendance_date END) AS late_days,
            COUNT(DISTINCT CASE WHEN status = 'absent' THEN attendance_date END) AS absent_days,
            COUNT(DISTINCT CASE WHEN status = 'on_leave' THEN attendance_date END) AS leave_days
         FROM attendance
         WHERE employee_id = ?
           AND attendance_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND attendance_date < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')"
    );
    $attendanceStmt->execute([$employeeId]);
    $attendanceSummary = array_merge($attendanceSummary, $attendanceStmt->fetch() ?: []);

    $recentAttendanceStmt = $pdo->prepare(
        "SELECT a.attendance_date, a.check_in, a.check_out, a.status,
                s.name AS shift_name, s.start_time, s.end_time
         FROM attendance a
         JOIN shifts s ON s.id = a.shift_id
         WHERE a.employee_id = ?
         ORDER BY a.attendance_date DESC, a.id DESC
         LIMIT 10"
    );
    $recentAttendanceStmt->execute([$employeeId]);
    $recentAttendance = $recentAttendanceStmt->fetchAll();

    $payrollStmt = $pdo->prepare(
        "SELECT * FROM payroll
         WHERE employee_id = ?
         ORDER BY period_year DESC, period_month DESC, id DESC
         LIMIT 1"
    );
    $payrollStmt->execute([$employeeId]);
    $latestPayroll = $payrollStmt->fetch() ?: null;

    $leaveStmt = $pdo->prepare(
        "SELECT id, start_date, end_date, reason, status, created_at
         FROM leaves
         WHERE employee_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 10"
    );
    $leaveStmt->execute([$employeeId]);
    $leaveHistory = $leaveStmt->fetchAll();

    $assignmentStmt = $pdo->prepare(
        "SELECT ea.assignment_date, ea.is_temporary,
                p.name AS port_name, p.location_name,
                s.name AS shift_name, s.start_time, s.end_time
         FROM employee_assignments ea
         JOIN ports p ON p.id = ea.port_id
         JOIN shifts s ON s.id = ea.shift_id
         WHERE ea.employee_id = ? AND ea.assignment_date = CURDATE()
         ORDER BY ea.assignment_date DESC, ea.id DESC
         LIMIT 1"
    );
    $assignmentStmt->execute([$employeeId]);
    $latestAssignment = $assignmentStmt->fetch() ?: null;
}

$employeeStatusLabels = [
    'active' => 'نشط',
    'on_leave' => 'في إجازة',
    'suspended' => 'موقوف',
    'terminated' => 'منتهي',
];
$applicationStatusLabels = [
    'submitted' => 'تم التقديم',
    'under_review' => 'قيد المراجعة',
    'shortlisted' => 'القائمة المختصرة',
    'interview' => 'مرحلة المقابلة',
    'accepted' => 'مقبول',
    'rejected' => 'غير مقبول',
    'account_created' => 'تم إنشاء الحساب',
    'withdrawn' => 'منسحب',
];
$identityTypeLabels = ['national_id' => 'هوية وطنية', 'residency' => 'إقامة', 'passport' => 'جواز سفر'];
$nationalityLabels = ['saudi' => 'سعودي', 'non_saudi' => 'غير سعودي'];
$genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
$maritalLabels = ['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل'];
$employmentTypeLabels = [
    'full_time' => 'دوام كامل',
    'part_time' => 'دوام جزئي',
    'temporary' => 'مؤقت',
    'temporary_contract' => 'عقد مؤقت',
    'contract' => 'عقد',
    'permanent' => 'دائم',
];
$educationLabels = [
    'high_school' => 'ثانوية عامة',
    'diploma' => 'دبلوم',
    'bachelor' => 'بكالوريوس',
    'master' => 'ماجستير',
    'doctorate' => 'دكتوراه',
    'other' => 'مؤهل آخر',
];
$sourceLabels = [
    'website' => 'الموقع الإلكتروني',
    'social_media' => 'وسائل التواصل',
    'referral' => 'ترشيح',
    'job_fair' => 'معرض توظيف',
    'other' => 'مصدر آخر',
];
$shiftLabels = ['morning' => 'صباحية', 'evening' => 'مسائية', 'night' => 'ليلية'];
$attendanceStatusLabels = ['present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر', 'on_leave' => 'إجازة'];
$leaveStatusLabels = ['pending' => 'قيد المراجعة', 'approved' => 'موافق عليها', 'rejected' => 'مرفوضة'];
$arabicMonths = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
$currentMonthLabel = $arabicMonths[(int)date('n')] . ' ' . date('Y');

$displayName = (string)(
    ($profile['application_full_name'] ?? '')
    ?: ($profile['account_full_name'] ?? $currentUserData['full_name'])
);
$employeeStatus = (string)($profile['employee_status'] ?? '');
$displayJobTitle = (string)(
    ($profile['job_title'] ?? '')
    ?: ($profile['advertised_job_title'] ?? 'موظف')
);
$displayDepartment = (string)(
    ($profile['employee_department'] ?? '')
    ?: ($profile['job_department'] ?? '—')
);
$displayEmail = (string)(
    ($profile['application_email'] ?? '')
    ?: ($profile['account_email'] ?? '')
);
$displayPort = (string)($profile['preferred_or_job_port_name'] ?? '');
$displayLocation = (string)($profile['preferred_or_job_location'] ?? '');

require __DIR__ . '/../../includes/header.php';
?>

<?php if ($employeeId < 1): ?>
    <section class="panel employment-profile-empty" aria-labelledby="profile-empty-title">
        <div class="employment-empty-mark" aria-hidden="true">!</div>
        <div>
            <h2 id="profile-empty-title">ملفك الوظيفي قيد التجهيز</h2>
            <p>مرحبًا <?= e((string)($profile['account_full_name'] ?? $currentUserData['full_name'])) ?>. تم تفعيل حسابك، لكن لم يكتمل ربطه بسجل الموظف بعد.</p>
            <p class="panel-hint">تواصل مع الموارد البشرية لاستكمال رقم الموظف وبيانات التعيين. لا توجد أي بيانات لموظفين آخرين معروضة في هذه الصفحة.</p>
        </div>
    </section>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php endif; ?>

<div class="employment-profile-shell">
    <section class="panel employment-profile-hero" aria-labelledby="employee-name">
        <div class="employment-profile-avatar" aria-hidden="true">
            <?= e(mb_substr($displayName, 0, 1)) ?>
        </div>
        <div class="employment-profile-identity">
            <div class="employment-profile-eyebrow">بوابة الموظف الذاتية</div>
            <h2 id="employee-name"><?= e($displayName) ?></h2>
            <p><?= e($displayJobTitle) ?> · <?= e($displayDepartment) ?></p>
            <div class="employment-profile-tags" aria-label="حالة الملف الوظيفي">
                <span class="badge <?= e(employmentProfileStatusClass($employeeStatus)) ?>">
                    <?= e($employeeStatusLabels[$employeeStatus] ?? 'غير محدد') ?>
                </span>
                <?php if (!empty($profile['employee_number'])): ?>
                    <span class="employment-reference" dir="ltr">#<?= e($profile['employee_number']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <dl class="employment-hero-facts">
            <div><dt>الميناء</dt><dd><?= e($displayPort ?: 'غير محدد') ?></dd></div>
            <div><dt>تاريخ التعيين</dt><dd><?= e(employmentProfileDate($profile['hire_date'])) ?></dd></div>
            <div><dt>نوع العقد</dt><dd><?= e($employmentTypeLabels[$profile['contract_type']] ?? $profile['contract_type'] ?? '—') ?></dd></div>
        </dl>
    </section>

    <div class="employment-profile-layout">
        <div class="employment-profile-main">
            <section class="panel employment-profile-section" aria-labelledby="identity-title">
                <div class="employment-section-heading">
                    <div><span>01</span><h2 id="identity-title">الهوية وبيانات التواصل</h2></div>
                    <p>البيانات المعتمدة من طلب التوظيف</p>
                </div>
                <dl class="employment-data-grid">
                    <div><dt>الاسم الكامل</dt><dd><?= e($displayName) ?></dd></div>
                    <div><dt>نوع الهوية</dt><dd><?= e($identityTypeLabels[$profile['identity_type']] ?? $profile['identity_type'] ?? '—') ?></dd></div>
                    <div><dt>رقم الهوية / الإقامة</dt><dd dir="ltr"><?= e($profile['identity_number'] ?: $profile['national_id'] ?: '—') ?></dd></div>
                    <div><dt>الجنسية</dt><dd><?= e($nationalityLabels[$profile['nationality']] ?? $profile['nationality'] ?? '—') ?></dd></div>
                    <div><dt>تاريخ الميلاد</dt><dd><?= e(employmentProfileDate($profile['birth_date'])) ?></dd></div>
                    <div><dt>الجنس</dt><dd><?= e($genderLabels[$profile['gender']] ?? $profile['gender'] ?? '—') ?></dd></div>
                    <div><dt>الحالة الاجتماعية</dt><dd><?= e($maritalLabels[$profile['marital_status']] ?? $profile['marital_status'] ?? '—') ?></dd></div>
                    <div><dt>عدد الأبناء</dt><dd><?= numberAr($profile['children_count'] ?? 0) ?></dd></div>
                    <div><dt>الجوال</dt><dd dir="ltr"><?= e($profile['mobile'] ?: '—') ?></dd></div>
                    <div><dt>الهاتف الثابت</dt><dd dir="ltr"><?= e($profile['phone'] ?: '—') ?></dd></div>
                    <div><dt>البريد الإلكتروني</dt><dd dir="ltr"><?= e($displayEmail ?: '—') ?></dd></div>
                    <div><dt>المدينة</dt><dd><?= e($profile['applicant_city'] ?: '—') ?></dd></div>
                    <div class="employment-data-wide"><dt>العنوان</dt><dd><?= e($profile['address'] ?: '—') ?></dd></div>
                </dl>
            </section>

            <section class="panel employment-profile-section" aria-labelledby="work-title">
                <div class="employment-section-heading">
                    <div><span>02</span><h2 id="work-title">الوظيفة وموقع العمل</h2></div>
                    <p>بيانات التعيين والتكليف الأحدث</p>
                </div>
                <dl class="employment-data-grid">
                    <div><dt>رقم الموظف</dt><dd dir="ltr"><?= e($profile['employee_number'] ?: '—') ?></dd></div>
                    <div><dt>المسمى الوظيفي</dt><dd><?= e($displayJobTitle) ?></dd></div>
                    <div><dt>الإدارة / القسم</dt><dd><?= e($displayDepartment) ?></dd></div>
                    <div><dt>الدرجة الوظيفية</dt><dd><?= e($profile['job_grade'] ?: '—') ?></dd></div>
                    <div><dt>نوع العمل</dt><dd><?= e($employmentTypeLabels[$profile['work_type']] ?? $employmentTypeLabels[$profile['advertised_employment_type']] ?? '—') ?></dd></div>
                    <div><dt>نوع العقد</dt><dd><?= e($employmentTypeLabels[$profile['contract_type']] ?? $profile['contract_type'] ?? '—') ?></dd></div>
                    <div><dt>تاريخ التعيين</dt><dd><?= e(employmentProfileDate($profile['hire_date'])) ?></dd></div>
                    <div><dt>نهاية العقد</dt><dd><?= e(employmentProfileDate($profile['contract_end_date'])) ?></dd></div>
                    <div><dt>الميناء</dt><dd><?= e($latestAssignment['port_name'] ?? $displayPort ?: '—') ?></dd></div>
                    <div><dt>الموقع</dt><dd><?= e($latestAssignment['location_name'] ?? $displayLocation ?: $profile['job_city'] ?: '—') ?></dd></div>
                    <div><dt>المناوبة الحالية</dt><dd><?= e($shiftLabels[$latestAssignment['shift_name'] ?? ''] ?? $latestAssignment['shift_name'] ?? '—') ?></dd></div>
                    <div><dt>وقت المناوبة</dt><dd dir="ltr"><?php if ($latestAssignment): ?><?= e(substr($latestAssignment['start_time'], 0, 5)) ?> — <?= e(substr($latestAssignment['end_time'], 0, 5)) ?><?php else: ?>—<?php endif; ?></dd></div>
                    <div><dt>المشرف المباشر</dt><dd><?= e($profile['supervisor_name'] ?: '—') ?></dd></div>
                    <div><dt>هاتف المشرف</dt><dd dir="ltr"><?= e($profile['supervisor_phone'] ?: '—') ?></dd></div>
                </dl>
            </section>

            <section class="panel employment-profile-section" aria-labelledby="attendance-title">
                <div class="employment-section-heading">
                    <div><span>03</span><h2 id="attendance-title">الحضور والانصراف</h2></div>
                    <p><?= e($currentMonthLabel) ?></p>
                </div>
                <div class="kpi-grid employment-attendance-kpis">
                    <div class="kpi-card tone-green"><span class="stat-label">أيام الحضور</span><span class="stat-value"><?= numberAr($attendanceSummary['present_days']) ?></span></div>
                    <div class="kpi-card warn-tone"><span class="stat-label">أيام التأخير</span><span class="stat-value"><?= numberAr($attendanceSummary['late_days']) ?></span></div>
                    <div class="kpi-card alert-tone"><span class="stat-label">أيام الغياب</span><span class="stat-value"><?= numberAr($attendanceSummary['absent_days']) ?></span></div>
                    <div class="kpi-card tone-blue"><span class="stat-label">أيام الإجازة</span><span class="stat-value"><?= numberAr($attendanceSummary['leave_days']) ?></span></div>
                </div>

                <div class="table-responsive">
                    <table>
                        <caption>أحدث سجلات الحضور والانصراف</caption>
                        <thead><tr><th>التاريخ</th><th>المناوبة</th><th>الحضور</th><th>الانصراف</th><th>الحالة</th></tr></thead>
                        <tbody>
                        <?php if (!$recentAttendance): ?>
                            <tr><td colspan="5">لا توجد سجلات حضور مسجلة حتى الآن.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentAttendance as $record): ?>
                                <tr>
                                    <td><?= e(employmentProfileDate($record['attendance_date'])) ?></td>
                                    <td><?= e($shiftLabels[$record['shift_name']] ?? $record['shift_name']) ?></td>
                                    <td dir="ltr"><?= e($record['check_in'] ? (new DateTimeImmutable($record['check_in']))->format('H:i') : '—') ?></td>
                                    <td dir="ltr"><?= e($record['check_out'] ? (new DateTimeImmutable($record['check_out']))->format('H:i') : '—') ?></td>
                                    <td><span class="badge <?= e(employmentProfileStatusClass($record['status'])) ?>"><?= e($attendanceStatusLabels[$record['status']] ?? $record['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel employment-profile-section" aria-labelledby="leave-title">
                <div class="employment-section-heading employment-section-heading-action">
                    <div><span>04</span><h2 id="leave-title">الإجازات</h2></div>
                    <details class="employment-leave-request">
                        <summary class="btn btn-outline">طلب إجازة جديدة</summary>
                        <form method="post" class="employment-leave-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="request_leave">
                            <div class="form-group">
                                <label for="leave-start">تاريخ البداية</label>
                                <input id="leave-start" type="date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="leave-end">تاريخ النهاية</label>
                                <input id="leave-end" type="date" name="end_date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group employment-form-wide">
                                <label for="leave-reason">السبب أو ملاحظات الطلب</label>
                                <textarea id="leave-reason" name="reason" rows="3" maxlength="255" placeholder="اختياري"></textarea>
                            </div>
                            <button class="btn btn-primary" type="submit">إرسال الطلب</button>
                        </form>
                    </details>
                </div>
                <div class="table-responsive">
                    <table>
                        <caption>سجل طلبات الإجازة</caption>
                        <thead><tr><th>من</th><th>إلى</th><th>المدة</th><th>السبب</th><th>الحالة</th></tr></thead>
                        <tbody>
                        <?php if (!$leaveHistory): ?>
                            <tr><td colspan="5">لا توجد طلبات إجازة مسجلة.</td></tr>
                        <?php else: ?>
                            <?php foreach ($leaveHistory as $leave): ?>
                                <?php
                                $leaveStart = new DateTimeImmutable($leave['start_date']);
                                $leaveEnd = new DateTimeImmutable($leave['end_date']);
                                $leaveDays = $leaveStart->diff($leaveEnd)->days + 1;
                                ?>
                                <tr>
                                    <td><?= e(employmentProfileDate($leave['start_date'])) ?></td>
                                    <td><?= e(employmentProfileDate($leave['end_date'])) ?></td>
                                    <td><?= numberAr($leaveDays) ?> يوم</td>
                                    <td><?= e($leave['reason'] ?: '—') ?></td>
                                    <td><span class="badge <?= e(employmentProfileStatusClass($leave['status'])) ?>"><?= e($leaveStatusLabels[$leave['status']] ?? $leave['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="employment-profile-aside" aria-label="ملخص الملف الوظيفي">
            <section class="panel employment-profile-section" aria-labelledby="salary-title">
                <div class="employment-section-heading employment-section-heading-compact">
                    <div><span>05</span><h2 id="salary-title">الراتب والمستحقات</h2></div>
                </div>
                <?php if ($latestPayroll): ?>
                    <p class="employment-period">آخر مسير: <?= numberAr($latestPayroll['period_month']) ?>/<?= numberAr($latestPayroll['period_year']) ?></p>
                    <dl class="employment-money-list">
                        <div><dt>الراتب الأساسي</dt><dd><?= e(employmentProfileMoney($latestPayroll['base_salary'])) ?></dd></div>
                        <div><dt>البدلات</dt><dd><?= e(employmentProfileMoney($latestPayroll['allowances'])) ?></dd></div>
                        <div><dt>العمل الإضافي</dt><dd><?= e(employmentProfileMoney($latestPayroll['overtime_amount'])) ?></dd></div>
                        <div><dt>المكافآت</dt><dd><?= e(employmentProfileMoney($latestPayroll['bonuses'])) ?></dd></div>
                        <div><dt>الاستقطاعات</dt><dd class="employment-negative">− <?= e(employmentProfileMoney($latestPayroll['deductions'])) ?></dd></div>
                        <div class="employment-money-total"><dt>صافي المستحق</dt><dd><?= e(employmentProfileMoney($latestPayroll['net_salary'])) ?></dd></div>
                    </dl>
                    <span class="badge <?= $latestPayroll['paid_status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>"><?= $latestPayroll['paid_status'] === 'paid' ? 'تم الصرف' : 'بانتظار الصرف' ?></span>
                <?php else: ?>
                    <div class="employment-salary-empty">
                        <span>الراتب الأساسي</span>
                        <strong><?= e(employmentProfileMoney($profile['base_salary'])) ?></strong>
                        <p>لم يصدر مسير راتب لهذا الحساب بعد.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel employment-profile-section" aria-labelledby="recruitment-title">
                <div class="employment-section-heading employment-section-heading-compact">
                    <div><span>06</span><h2 id="recruitment-title">ملف التوظيف</h2></div>
                </div>
                <dl class="employment-data-stack">
                    <div><dt>رقم الطلب</dt><dd dir="ltr"><?= e($profile['application_reference'] ?: '—') ?></dd></div>
                    <div><dt>رقم الوظيفة</dt><dd dir="ltr"><?= e($profile['job_reference'] ?: '—') ?></dd></div>
                    <div><dt>الوظيفة المعلن عنها</dt><dd><?= e($profile['advertised_job_title'] ?: '—') ?></dd></div>
                    <div><dt>حالة الطلب</dt><dd><span class="badge <?= e(employmentProfileStatusClass((string)$profile['application_status'])) ?>"><?= e($applicationStatusLabels[$profile['application_status']] ?? $profile['application_status'] ?? '—') ?></span></dd></div>
                    <div><dt>تاريخ التقديم</dt><dd><?= e(employmentProfileDate($profile['submitted_at'], true)) ?></dd></div>
                    <div><dt>تاريخ القبول</dt><dd><?= e(employmentProfileDate($profile['accepted_at'], true)) ?></dd></div>
                    <div><dt>الميناء المفضل</dt><dd><?= e($profile['preferred_port_name'] ?: '—') ?></dd></div>
                    <div><dt>مصدر معرفة الوظيفة</dt><dd><?= e($sourceLabels[$profile['source']] ?? $profile['source'] ?? '—') ?></dd></div>
                </dl>
            </section>

            <section class="panel employment-profile-section" aria-labelledby="qualification-title">
                <div class="employment-section-heading employment-section-heading-compact">
                    <div><span>07</span><h2 id="qualification-title">المؤهلات والخبرة</h2></div>
                </div>
                <dl class="employment-data-stack">
                    <div><dt>المؤهل العلمي</dt><dd><?= e($educationLabels[$profile['education_level']] ?? $profile['education_level'] ?? '—') ?></dd></div>
                    <div><dt>التخصص</dt><dd><?= e($profile['specialization'] ?: '—') ?></dd></div>
                    <div><dt>جهة الدراسة</dt><dd><?= e($profile['institution'] ?: '—') ?></dd></div>
                    <div><dt>سنة التخرج</dt><dd><?= e((string)($profile['graduation_year'] ?: '—')) ?></dd></div>
                    <div><dt>سنوات الخبرة</dt><dd><?= numberAr($profile['experience_years'] ?? 0, 1) ?> سنة</dd></div>
                    <div><dt>جهة العمل السابقة</dt><dd><?= e($profile['current_employer'] ?: '—') ?></dd></div>
                    <div><dt>المسمى السابق</dt><dd><?= e($profile['current_job_title'] ?: '—') ?></dd></div>
                    <div><dt>تاريخ الجاهزية</dt><dd><?= e(employmentProfileDate($profile['availability_date'])) ?></dd></div>
                </dl>
                <div class="employment-profile-copy">
                    <h3>المهارات</h3>
                    <p><?= nl2br(e($profile['skills'] ?: 'لم تُسجل مهارات.')) ?></p>
                </div>
                <?php if (!empty($profile['professional_summary'])): ?>
                    <div class="employment-profile-copy">
                        <h3>الملخص المهني</h3>
                        <p><?= nl2br(e($profile['professional_summary'])) ?></p>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
