<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'hr_manager']);
$pageTitle = 'طلبات التوظيف';
$activeRoute = 'job_applications.php';
$pageStyles = ['css/employment.css'];

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

$pdo = db();
$applicationsUrl = BASE_URL . '/dashboard/job_applications.php';

$statusLabels = [
    'submitted' => 'طلب جديد',
    'under_review' => 'قيد المراجعة',
    'shortlisted' => 'القائمة المختصرة',
    'interview' => 'مقابلة',
    'accepted' => 'مقبول',
    'rejected' => 'غير مقبول',
    'account_created' => 'تم إنشاء الحساب',
    'withdrawn' => 'منسحب',
];
$employmentTypeLabels = [
    'full_time' => 'دوام كامل',
    'part_time' => 'دوام جزئي',
    'temporary' => 'عمل مؤقت',
    'contract' => 'عقد',
];
$identityTypeLabels = [
    'national_id' => 'هوية وطنية',
    'residency' => 'إقامة',
    'passport' => 'جواز سفر',
];
$genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
$maritalLabels = [
    'single' => 'أعزب / عزباء',
    'married' => 'متزوج / متزوجة',
    'divorced' => 'مطلق / مطلقة',
    'widowed' => 'أرمل / أرملة',
];
$educationLabels = [
    'high_school' => 'ثانوية عامة',
    'diploma' => 'دبلوم',
    'bachelor' => 'بكالوريوس',
    'master' => 'ماجستير',
    'doctorate' => 'دكتوراه',
    'other' => 'أخرى',
];
$sourceLabels = [
    'website' => 'الموقع الإلكتروني',
    'social_media' => 'وسائل التواصل',
    'referral' => 'ترشيح',
    'job_fair' => 'معرض توظيف',
    'other' => 'أخرى',
];
$attachmentLabels = [
    'cv' => 'السيرة الذاتية',
    'identity' => 'وثيقة الهوية',
    'certificate' => 'المؤهل أو الشهادة',
    'other' => 'مرفق إضافي',
];
$eventLabels = [
    'submitted' => 'استلام الطلب',
    'status_changed' => 'تغيير حالة الطلب',
    'note_updated' => 'تحديث ملاحظة المراجعة',
    'account_created' => 'إنشاء حساب الموظف',
];

$allowedTransitions = [
    'submitted' => ['under_review', 'rejected'],
    'under_review' => ['shortlisted', 'interview', 'accepted', 'rejected'],
    'shortlisted' => ['under_review', 'interview', 'accepted', 'rejected'],
    'interview' => ['under_review', 'shortlisted', 'accepted', 'rejected'],
    'accepted' => ['under_review', 'rejected'],
    'rejected' => ['under_review'],
    'account_created' => [],
    'withdrawn' => [],
];

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

$oneTimeCredentials = null;
if (isset($_SESSION['employment_credentials_once']) && is_array($_SESSION['employment_credentials_once'])) {
    $oneTimeCredentials = $_SESSION['employment_credentials_once'];
    unset($_SESSION['employment_credentials_once']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = max(0, (int)($_POST['application_id'] ?? 0));
    $returnUrl = $applicationsUrl . ($applicationId > 0 ? '?application_id=' . $applicationId : '');

    if (!verifyCsrf()) {
        redirectWithMessage($returnUrl, 'error', 'انتهت صلاحية الجلسة. أعد المحاولة.');
    }

    $action = (string)($_POST['action'] ?? '');

    try {
        if ($applicationId < 1) {
            throw new DomainException('طلب التوظيف المحدد غير صالح.');
        }

        if ($action === 'save_review') {
            $targetStatus = (string)($_POST['status'] ?? '');
            $adminNote = trim((string)($_POST['admin_note'] ?? ''));
            if (mb_strlen($adminNote) > 5000) {
                throw new DomainException('ملاحظة المراجعة أطول من الحد المسموح.');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'SELECT id, status, accepted_at, employee_user_id
                 FROM employment_applications WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application) {
                throw new DomainException('طلب التوظيف غير موجود.');
            }

            $fromStatus = $application['status'];
            if ($fromStatus === 'account_created' || $application['employee_user_id'] !== null) {
                throw new DomainException('تم إنشاء حساب لهذا الموظف، ولا يمكن إعادة الطلب إلى مرحلة سابقة.');
            }
            if ($fromStatus === 'withdrawn') {
                throw new DomainException('الطلب منسحب ولا يمكن تغيير حالته.');
            }
            if ($targetStatus !== $fromStatus
                && !in_array($targetStatus, $allowedTransitions[$fromStatus] ?? [], true)) {
                throw new DomainException('الانتقال المطلوب غير مسموح من حالة الطلب الحالية.');
            }
            if (!isset($statusLabels[$targetStatus]) || $targetStatus === 'account_created') {
                throw new DomainException('حالة المراجعة المطلوبة غير صالحة.');
            }

            $acceptedAt = $targetStatus === 'accepted'
                ? ($application['accepted_at'] ?: date('Y-m-d H:i:s'))
                : null;

            $pdo->prepare(
                'UPDATE employment_applications
                 SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW(), accepted_at = ?
                 WHERE id = ?'
            )->execute([
                $targetStatus,
                $adminNote !== '' ? $adminNote : null,
                $currentUserData['id'],
                $acceptedAt,
                $applicationId,
            ]);

            $eventType = $targetStatus === $fromStatus ? 'note_updated' : 'status_changed';
            $pdo->prepare(
                'INSERT INTO employment_application_events
                    (application_id, event_type, from_status, to_status, note, actor_user_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $applicationId,
                $eventType,
                $fromStatus,
                $targetStatus,
                $adminNote !== '' ? $adminNote : null,
                $currentUserData['id'],
            ]);
            $pdo->commit();

            $message = $targetStatus === $fromStatus
                ? 'تم حفظ ملاحظة المراجعة.'
                : 'تم تحديث حالة الطلب إلى «' . $statusLabels[$targetStatus] . '».';
            redirectWithMessage($returnUrl, 'success', $message);
        }

        if ($action !== 'provision_account') {
            throw new DomainException('الإجراء المطلوب غير صالح.');
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $employeeNumber = trim((string)($_POST['employee_number'] ?? ''));
        $hireDate = trim((string)($_POST['hire_date'] ?? ''));
        $contractType = (string)($_POST['contract_type'] ?? 'permanent');
        $contractEndDate = trim((string)($_POST['contract_end_date'] ?? ''));
        $baseSalaryRaw = trim((string)($_POST['base_salary'] ?? '0'));
        $jobTitle = trim((string)($_POST['job_title'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));
        $jobGrade = trim((string)($_POST['job_grade'] ?? ''));
        $supervisorName = trim((string)($_POST['supervisor_name'] ?? ''));
        $supervisorPhone = trim((string)($_POST['supervisor_phone'] ?? ''));
        $selectedPortId = max(0, (int)($_POST['selected_port_id'] ?? 0));
        $selectedShiftId = max(0, (int)($_POST['selected_shift_id'] ?? 0));

        $validationErrors = [];
        if (!preg_match('/\A[A-Za-z0-9._-]{4,100}\z/', $username)) {
            $validationErrors[] = 'اسم المستخدم يجب أن يتكون من 4 إلى 100 حرف لاتيني أو رقم، ويمكن استخدام النقطة والشرطة.';
        }
        if (strlen($password) < 10 || strlen($password) > 200) {
            $validationErrors[] = 'كلمة المرور يجب أن تكون بين 10 و200 محرف.';
        }
        if ($employeeNumber === '' || mb_strlen($employeeNumber) > 40) {
            $validationErrors[] = 'أدخل رقمًا وظيفيًا صحيحًا لا يتجاوز 40 حرفًا.';
        }
        if (!$isValidDate($hireDate) || $hireDate === '') {
            $validationErrors[] = 'تاريخ المباشرة غير صالح.';
        }
        if (!in_array($contractType, ['permanent', 'temporary'], true)) {
            $validationErrors[] = 'نوع العقد غير صالح.';
        }
        if (!$isValidDate($contractEndDate)) {
            $validationErrors[] = 'تاريخ نهاية العقد غير صالح.';
        }
        if ($contractType === 'temporary' && $contractEndDate === '') {
            $validationErrors[] = 'حدد تاريخ نهاية العقد المؤقت.';
        }
        if ($contractEndDate !== '' && $hireDate !== '' && $contractEndDate < $hireDate) {
            $validationErrors[] = 'نهاية العقد يجب أن تكون بعد تاريخ المباشرة.';
        }
        if ($baseSalaryRaw === '') {
            $baseSalaryRaw = '0';
        }
        if (!is_numeric($baseSalaryRaw) || (float)$baseSalaryRaw < 0 || (float)$baseSalaryRaw > 99999999.99) {
            $validationErrors[] = 'الراتب الأساسي غير صالح.';
        }
        if ($jobTitle === '' || mb_strlen($jobTitle) > 190) {
            $validationErrors[] = 'المسمى الوظيفي مطلوب ولا يتجاوز 190 حرفًا.';
        }
        if (mb_strlen($department) > 190 || mb_strlen($jobGrade) > 80
            || mb_strlen($supervisorName) > 190 || mb_strlen($supervisorPhone) > 30) {
            $validationErrors[] = 'إحدى بيانات الوظيفة أو المشرف أطول من الحد المسموح.';
        }
        if (($selectedPortId > 0) xor ($selectedShiftId > 0)) {
            $validationErrors[] = 'لإنشاء تكليف، اختر الميناء والمناوبة معًا، أو اتركهما معًا.';
        }
        if ($validationErrors) {
            throw new DomainException(implode(' ', $validationErrors));
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'SELECT a.*, j.title_ar AS job_title_default, j.department AS job_department,
                    j.port_id AS job_port_id
             FROM employment_applications a
             JOIN employment_jobs j ON j.id = a.job_id
             WHERE a.id = ? FOR UPDATE'
        );
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch();
        if (!$application) {
            throw new DomainException('طلب التوظيف غير موجود.');
        }
        if ($application['status'] !== 'accepted') {
            throw new DomainException('لا يمكن إنشاء الحساب إلا بعد اعتماد الطلب بالحالة «مقبول».');
        }
        if ($application['employee_user_id'] !== null) {
            throw new DomainException('تم إنشاء حساب لهذا الطلب مسبقًا.');
        }

        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE code = 'employee_portal' LIMIT 1");
        $roleStmt->execute();
        $employeeRoleId = (int)$roleStmt->fetchColumn();
        if ($employeeRoleId < 1) {
            throw new DomainException('دور بوابة الموظف غير مهيأ. طبّق تحديث قاعدة بيانات التوظيف أولًا.');
        }

        $uniqueStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $uniqueStmt->execute([$username]);
        if ($uniqueStmt->fetchColumn()) {
            throw new DomainException('اسم المستخدم مستخدم بالفعل. اختر اسمًا آخر.');
        }
        $uniqueStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $uniqueStmt->execute([$application['email']]);
        if ($uniqueStmt->fetchColumn()) {
            throw new DomainException('البريد الإلكتروني للمتقدم مرتبط بحساب مستخدم موجود بالفعل.');
        }
        $uniqueStmt = $pdo->prepare('SELECT id FROM employees WHERE employee_number = ? LIMIT 1');
        $uniqueStmt->execute([$employeeNumber]);
        if ($uniqueStmt->fetchColumn()) {
            throw new DomainException('الرقم الوظيفي مستخدم بالفعل.');
        }

        if ($selectedPortId > 0) {
            $portStmt = $pdo->prepare('SELECT id FROM ports WHERE id = ? AND is_active = 1 LIMIT 1');
            $portStmt->execute([$selectedPortId]);
            if (!$portStmt->fetchColumn()) {
                throw new DomainException('الميناء المختار غير متاح للتكليف.');
            }
            $shiftStmt = $pdo->prepare('SELECT id FROM shifts WHERE id = ? LIMIT 1');
            $shiftStmt->execute([$selectedShiftId]);
            if (!$shiftStmt->fetchColumn()) {
                throw new DomainException('المناوبة المختارة غير موجودة.');
            }
        }

        $pdo->prepare(
            'INSERT INTO users
                (role_id, full_name, username, email, password_hash, port_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        )->execute([
            $employeeRoleId,
            $application['full_name'],
            $username,
            $application['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $selectedPortId > 0 ? $selectedPortId : null,
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        $employeeNationalId = mb_strlen((string)$application['identity_number']) <= 20
            ? $application['identity_number']
            : null;
        $pdo->prepare(
            'INSERT INTO employees
                (user_id, employment_application_id, employee_number, national_id, job_title, department,
                 job_grade, supervisor_name, supervisor_phone, hire_date, contract_type,
                 contract_end_date, base_salary, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\')'
        )->execute([
            $newUserId,
            $applicationId,
            $employeeNumber,
            $employeeNationalId,
            $jobTitle,
            $department !== '' ? $department : null,
            $jobGrade !== '' ? $jobGrade : null,
            $supervisorName !== '' ? $supervisorName : null,
            $supervisorPhone !== '' ? $supervisorPhone : null,
            $hireDate,
            $contractType,
            $contractEndDate !== '' ? $contractEndDate : null,
            number_format((float)$baseSalaryRaw, 2, '.', ''),
        ]);
        $newEmployeeId = (int)$pdo->lastInsertId();

        if ($selectedPortId > 0 && $selectedShiftId > 0) {
            $pdo->prepare(
                'INSERT INTO employee_assignments
                    (employee_id, port_id, shift_id, assignment_date, is_temporary)
                 VALUES (?, ?, ?, ?, 0)'
            )->execute([$newEmployeeId, $selectedPortId, $selectedShiftId, $hireDate]);
        }

        $pdo->prepare(
            "UPDATE employment_applications
             SET status = 'account_created', employee_user_id = ?, reviewed_by = ?, reviewed_at = NOW()
             WHERE id = ?"
        )->execute([$newUserId, $currentUserData['id'], $applicationId]);

        $pdo->prepare(
            'INSERT INTO employment_application_events
                (application_id, event_type, from_status, to_status, note, actor_user_id)
             VALUES (?, \'account_created\', \'accepted\', \'account_created\', ?, ?)'
        )->execute([
            $applicationId,
            'تم إنشاء حساب الموظف وربطه بالرقم الوظيفي ' . $employeeNumber . '.',
            $currentUserData['id'],
        ]);
        $pdo->commit();

        $_SESSION['employment_credentials_once'] = [
            'application_id' => $applicationId,
            'full_name' => $application['full_name'],
            'username' => $username,
            'password' => $password,
            'employee_number' => $employeeNumber,
        ];
        redirectWithMessage($returnUrl, 'success', 'تم إنشاء حساب الموظف وربطه بطلب التوظيف.');
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirectWithMessage($returnUrl, 'error', $e->getMessage());
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Employment applications error: ' . $e->getMessage());
        redirectWithMessage($returnUrl, 'error', 'تعذر تنفيذ الإجراء. تحقق من عدم تكرار اسم المستخدم أو الرقم الوظيفي ثم أعد المحاولة.');
    }
}

$jobs = $pdo->query(
    'SELECT id, reference_no, title_ar, status FROM employment_jobs ORDER BY created_at DESC'
)->fetchAll();
$ports = $pdo->query(
    'SELECT p.id, p.name, g.name AS governorate_name
     FROM ports p JOIN governorates g ON g.id = p.governorate_id
     WHERE p.is_active = 1 ORDER BY g.name, p.name'
)->fetchAll();
$shifts = $pdo->query('SELECT id, name, start_time, end_time FROM shifts ORDER BY start_time')->fetchAll();
$shiftLabels = ['morning' => 'صباحية', 'evening' => 'مسائية', 'night' => 'ليلية'];

$statsRow = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'submitted') AS submitted_count,
            SUM(status IN ('under_review', 'shortlisted', 'interview')) AS active_review_count,
            SUM(status = 'accepted') AS accepted_count,
            SUM(status = 'account_created') AS accounts_count
     FROM employment_applications"
)->fetch() ?: [];
$stats = [
    'total' => (int)($statsRow['total'] ?? 0),
    'submitted' => (int)($statsRow['submitted_count'] ?? 0),
    'active_review' => (int)($statsRow['active_review_count'] ?? 0),
    'accepted' => (int)($statsRow['accepted_count'] ?? 0),
    'accounts' => (int)($statsRow['accounts_count'] ?? 0),
];

$statusFilter = (string)($_GET['status'] ?? 'all');
if ($statusFilter !== 'all' && !isset($statusLabels[$statusFilter])) {
    $statusFilter = 'all';
}
$jobFilter = max(0, (int)($_GET['job_id'] ?? 0));
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;

$where = [];
$params = [];
if ($statusFilter !== 'all') {
    $where[] = 'a.status = ?';
    $params[] = $statusFilter;
}
if ($jobFilter > 0) {
    $where[] = 'a.job_id = ?';
    $params[] = $jobFilter;
}
if ($search !== '') {
    $needle = '%' . $search . '%';
    $where[] = '(a.reference_no LIKE ? OR a.full_name LIKE ? OR a.email LIKE ? OR a.mobile LIKE ? OR j.title_ar LIKE ?)';
    array_push($params, $needle, $needle, $needle, $needle, $needle);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM employment_applications a
     JOIN employment_jobs j ON j.id = a.job_id $whereSql"
);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT a.id, a.reference_no, a.status, a.full_name, a.email, a.mobile,
            a.experience_years, a.submitted_at, a.employee_user_id,
            j.id AS job_id, j.title_ar AS job_title, j.reference_no AS job_reference,
            p.name AS preferred_port_name
     FROM employment_applications a
     JOIN employment_jobs j ON j.id = a.job_id
     LEFT JOIN ports p ON p.id = a.preferred_port_id
     $whereSql
     ORDER BY a.submitted_at DESC, a.id DESC
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($params);
$applications = $listStmt->fetchAll();

$selectedApplication = null;
$attachments = [];
$events = [];
$selectedApplicationId = max(0, (int)($_GET['application_id'] ?? 0));
if ($selectedApplicationId > 0) {
    $detailStmt = $pdo->prepare(
        'SELECT a.*, j.title_ar AS job_title, j.reference_no AS job_reference,
                j.department AS job_department, j.port_id AS job_port_id,
                j.salary_min AS job_salary_min, j.salary_max AS job_salary_max,
                preferred_port.name AS preferred_port_name,
                job_port.name AS job_port_name,
                reviewer.full_name AS reviewer_name,
                linked_user.username AS linked_username
         FROM employment_applications a
         JOIN employment_jobs j ON j.id = a.job_id
         LEFT JOIN ports preferred_port ON preferred_port.id = a.preferred_port_id
         LEFT JOIN ports job_port ON job_port.id = j.port_id
         LEFT JOIN users reviewer ON reviewer.id = a.reviewed_by
         LEFT JOIN users linked_user ON linked_user.id = a.employee_user_id
         WHERE a.id = ? LIMIT 1'
    );
    $detailStmt->execute([$selectedApplicationId]);
    $selectedApplication = $detailStmt->fetch() ?: null;

    if ($selectedApplication) {
        $attachmentStmt = $pdo->prepare(
            'SELECT * FROM employment_application_attachments
             WHERE application_id = ? ORDER BY created_at, id'
        );
        $attachmentStmt->execute([$selectedApplicationId]);
        $attachments = $attachmentStmt->fetchAll();

        $eventStmt = $pdo->prepare(
            'SELECT ev.*, u.full_name AS actor_name
             FROM employment_application_events ev
             LEFT JOIN users u ON u.id = ev.actor_user_id
             WHERE ev.application_id = ? ORDER BY ev.created_at DESC, ev.id DESC'
        );
        $eventStmt->execute([$selectedApplicationId]);
        $events = $eventStmt->fetchAll();
    }
}

$filterQuery = [
    'q' => $search !== '' ? $search : null,
    'status' => $statusFilter !== 'all' ? $statusFilter : null,
    'job_id' => $jobFilter > 0 ? $jobFilter : null,
];
$filterQuery = array_filter($filterQuery, static fn($value) => $value !== null);

$suggestedUsername = '';
$suggestedEmployeeNumber = '';
$suggestedPortId = 0;
if ($selectedApplication) {
    $emailLocalPart = strtolower((string)strtok($selectedApplication['email'], '@'));
    $suggestedUsername = preg_replace('/[^a-z0-9._-]+/', '.', $emailLocalPart) ?: '';
    $suggestedUsername = trim($suggestedUsername, '.-_');
    if (strlen($suggestedUsername) < 4) {
        $suggestedUsername = 'employee.' . $selectedApplication['id'];
    }
    $suggestedUsername = substr($suggestedUsername, 0, 100);
    $suggestedEmployeeNumber = 'EMP-' . date('Y') . '-' . str_pad((string)$selectedApplication['id'], 6, '0', STR_PAD_LEFT);
    $suggestedPortId = (int)($selectedApplication['preferred_port_id'] ?: $selectedApplication['job_port_id']);
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="employment-admin-shell">
    <section class="employment-admin-hero applications-hero">
        <div>
            <span class="employment-eyebrow">غرفة فرز المرشحين</span>
            <h2>كل قرار موثّق، وكل تعيين يبدأ من طلب مقبول</h2>
            <p>راجع بيانات المتقدم ومرفقاته، حدّث مرحلة الطلب، ثم أنشئ حساب الموظف عند اكتمال الاعتماد.</p>
        </div>
        <div class="employment-hero-actions">
            <a class="btn btn-outline" href="<?= e(BASE_URL) ?>/dashboard/jobs.php">إدارة الفرص</a>
        </div>
    </section>

    <nav class="employment-workflow-nav" aria-label="التوظيف">
        <a href="<?= e(BASE_URL) ?>/dashboard/jobs.php">الفرص الوظيفية</a>
        <a class="active" href="<?= e($applicationsUrl) ?>">طلبات المتقدمين</a>
    </nav>

    <?php if ($oneTimeCredentials): ?>
        <section class="employment-credentials-once" role="alert" aria-live="assertive">
            <div>
                <span>بيانات دخول لمرة واحدة</span>
                <h3>تم إنشاء حساب <?= e($oneTimeCredentials['full_name'] ?? '') ?></h3>
                <p>انسخ البيانات وسلّمها للموظف عبر قناة آمنة الآن؛ لن تظهر كلمة المرور مجددًا.</p>
            </div>
            <dl>
                <div><dt>الرقم الوظيفي</dt><dd dir="ltr"><?= e($oneTimeCredentials['employee_number'] ?? '') ?></dd></div>
                <div><dt>اسم المستخدم</dt><dd dir="ltr"><?= e($oneTimeCredentials['username'] ?? '') ?></dd></div>
                <div><dt>كلمة المرور المؤقتة</dt><dd dir="ltr"><?= e($oneTimeCredentials['password'] ?? '') ?></dd></div>
            </dl>
        </section>
    <?php endif; ?>

    <section class="employment-kpis" aria-label="مؤشرات الطلبات">
        <article><span>إجمالي الطلبات</span><strong><?= numberAr($stats['total']) ?></strong></article>
        <article class="tone-live"><span>طلبات جديدة</span><strong><?= numberAr($stats['submitted']) ?></strong></article>
        <article><span>في مسار المراجعة</span><strong><?= numberAr($stats['active_review']) ?></strong></article>
        <article><span>بانتظار إنشاء حساب</span><strong><?= numberAr($stats['accepted']) ?></strong></article>
        <article><span>حسابات منشأة</span><strong><?= numberAr($stats['accounts']) ?></strong></article>
    </section>

    <section class="panel employment-list-panel">
        <header class="employment-section-heading">
            <div><span>صندوق الطلبات</span><h3>فرز وبحث المتقدمين</h3></div>
            <small><?= numberAr($totalRows) ?> نتيجة</small>
        </header>

        <form method="get" class="employment-filter-bar">
            <label class="employment-search-field">بحث
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="الاسم، المرجع، البريد أو الجوال">
            </label>
            <label>الحالة
                <select name="status">
                    <option value="all">كل الحالات</option>
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>الفرصة
                <select name="job_id">
                    <option value="">كل الفرص</option>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?= (int)$job['id'] ?>" <?= $jobFilter === (int)$job['id'] ? 'selected' : '' ?>><?= e($job['reference_no'] . ' — ' . $job['title_ar']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-outline" type="submit">تطبيق</button>
            <?php if ($filterQuery): ?><a class="btn btn-outline" href="<?= e($applicationsUrl) ?>">مسح</a><?php endif; ?>
        </form>

        <?php if (!$applications): ?>
            <div class="employment-empty-state"><strong>لا توجد طلبات مطابقة</strong><p>جرّب تغيير المرشحات أو اختيار فرصة أخرى.</p></div>
        <?php else: ?>
            <div class="employment-table-wrap">
                <table class="employment-applications-table">
                    <thead><tr><th>المتقدم</th><th>الفرصة</th><th>الخبرة</th><th>تاريخ التقديم</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td><strong><?= e($application['full_name']) ?></strong><small dir="ltr"><?= e($application['reference_no']) ?> · <?= e($application['mobile']) ?></small></td>
                            <td><strong><?= e($application['job_title']) ?></strong><small><?= e($application['preferred_port_name'] ?: $application['job_reference']) ?></small></td>
                            <td><?= numberAr($application['experience_years'], 1) ?> سنة</td>
                            <td><?= e(date('Y-m-d', strtotime($application['submitted_at']))) ?></td>
                            <td><span class="employment-status status-<?= e($application['status']) ?>"><?= e($statusLabels[$application['status']]) ?></span></td>
                            <td><a class="btn btn-outline btn-sm" href="<?= e($applicationsUrl) ?>?application_id=<?= (int)$application['id'] ?>">فتح الملف</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="employment-pagination" aria-label="صفحات الطلبات">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-outline btn-sm" href="?<?= e(http_build_query($filterQuery + ['page' => $page - 1])) ?>">السابق</a>
                    <?php endif; ?>
                    <span>صفحة <?= numberAr($page) ?> من <?= numberAr($totalPages) ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-outline btn-sm" href="?<?= e(http_build_query($filterQuery + ['page' => $page + 1])) ?>">التالي</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if ($selectedApplication): ?>
        <section class="employment-application-detail" id="application-detail">
            <header class="employment-profile-header">
                <div class="employment-applicant-monogram" aria-hidden="true"><?= e(mb_substr($selectedApplication['full_name'], 0, 1)) ?></div>
                <div>
                    <span dir="ltr"><?= e($selectedApplication['reference_no']) ?></span>
                    <h2><?= e($selectedApplication['full_name']) ?></h2>
                    <p><?= e($selectedApplication['job_title']) ?> · <?= e($selectedApplication['job_reference']) ?></p>
                </div>
                <span class="employment-status status-<?= e($selectedApplication['status']) ?>"><?= e($statusLabels[$selectedApplication['status']]) ?></span>
                <a class="btn btn-outline btn-sm" href="<?= e($applicationsUrl) ?>">إغلاق الملف</a>
            </header>

            <div class="employment-detail-grid">
                <div class="employment-detail-main">
                    <article class="panel employment-info-section">
                        <header><span>01</span><h3>البيانات الشخصية والتواصل</h3></header>
                        <dl class="employment-data-grid">
                            <div><dt>الاسم الكامل</dt><dd><?= e($selectedApplication['full_name']) ?></dd></div>
                            <div><dt>الجنسية</dt><dd><?= e($selectedApplication['nationality']) ?></dd></div>
                            <div><dt>نوع الهوية</dt><dd><?= e($identityTypeLabels[$selectedApplication['identity_type']] ?? $selectedApplication['identity_type']) ?></dd></div>
                            <div><dt>رقم الهوية</dt><dd dir="ltr"><?= e($selectedApplication['identity_number']) ?></dd></div>
                            <div><dt>تاريخ الميلاد</dt><dd><?= e($selectedApplication['birth_date']) ?></dd></div>
                            <div><dt>الجنس</dt><dd><?= e($genderLabels[$selectedApplication['gender']] ?? $selectedApplication['gender']) ?></dd></div>
                            <div><dt>الحالة الاجتماعية</dt><dd><?= e($maritalLabels[$selectedApplication['marital_status']] ?? $selectedApplication['marital_status']) ?></dd></div>
                            <div><dt>عدد الأبناء</dt><dd><?= numberAr($selectedApplication['children_count']) ?></dd></div>
                            <div><dt>الجوال</dt><dd dir="ltr"><?= e($selectedApplication['mobile']) ?></dd></div>
                            <div><dt>الهاتف</dt><dd dir="ltr"><?= e($selectedApplication['phone'] ?: '—') ?></dd></div>
                            <div><dt>البريد الإلكتروني</dt><dd dir="ltr"><?= e($selectedApplication['email']) ?></dd></div>
                            <div><dt>المدينة</dt><dd><?= e($selectedApplication['city']) ?></dd></div>
                            <div class="span-2"><dt>العنوان</dt><dd><?= nl2br(e($selectedApplication['address'])) ?></dd></div>
                        </dl>
                    </article>

                    <article class="panel employment-info-section">
                        <header><span>02</span><h3>المؤهلات والخبرة</h3></header>
                        <dl class="employment-data-grid">
                            <div><dt>المؤهل</dt><dd><?= e($educationLabels[$selectedApplication['education_level']] ?? $selectedApplication['education_level']) ?></dd></div>
                            <div><dt>التخصص</dt><dd><?= e($selectedApplication['specialization']) ?></dd></div>
                            <div><dt>الجهة التعليمية</dt><dd><?= e($selectedApplication['institution']) ?></dd></div>
                            <div><dt>سنة التخرج</dt><dd><?= e((string)($selectedApplication['graduation_year'] ?: '—')) ?></dd></div>
                            <div><dt>سنوات الخبرة</dt><dd><?= numberAr($selectedApplication['experience_years'], 1) ?> سنة</dd></div>
                            <div><dt>جهة العمل الحالية</dt><dd><?= e($selectedApplication['current_employer'] ?: '—') ?></dd></div>
                            <div><dt>المسمى الحالي</dt><dd><?= e($selectedApplication['current_job_title'] ?: '—') ?></dd></div>
                            <div><dt>الجاهزية للمباشرة</dt><dd><?= e($selectedApplication['availability_date'] ?: 'غير محدد') ?></dd></div>
                            <div><dt>نمط العمل المطلوب</dt><dd><?= e($employmentTypeLabels[$selectedApplication['work_type']] ?? $selectedApplication['work_type']) ?></dd></div>
                            <div><dt>الميناء المفضل</dt><dd><?= e($selectedApplication['preferred_port_name'] ?: 'غير محدد') ?></dd></div>
                            <div><dt>مصدر التعرف</dt><dd><?= e($sourceLabels[$selectedApplication['source']] ?? $selectedApplication['source']) ?></dd></div>
                            <div><dt>الموافقة على الإقرار</dt><dd><?= (int)$selectedApplication['consent'] === 1 ? 'نعم' : 'لا' ?></dd></div>
                        </dl>
                        <div class="employment-prose-block"><h4>الملخص المهني</h4><p><?= nl2br(e($selectedApplication['professional_summary'] ?: 'لم يُضف المتقدم ملخصًا.')) ?></p></div>
                        <div class="employment-prose-block"><h4>المهارات</h4><p><?= nl2br(e($selectedApplication['skills'])) ?></p></div>
                        <div class="employment-prose-block"><h4>خطاب التقديم</h4><p><?= nl2br(e($selectedApplication['cover_letter'] ?: 'لم يُرفق نص خطاب.')) ?></p></div>
                    </article>

                    <article class="panel employment-info-section">
                        <header><span>03</span><h3>المرفقات</h3></header>
                        <?php if (!$attachments): ?>
                            <p class="employment-muted">لا توجد مرفقات محفوظة لهذا الطلب.</p>
                        <?php else: ?>
                            <div class="employment-attachment-list">
                                <?php foreach ($attachments as $attachment): ?>
                                    <a href="<?= e(BASE_URL) ?>/dashboard/employment_attachment.php?id=<?= (int)$attachment['id'] ?>">
                                        <span><?= e($attachmentLabels[$attachment['attachment_type']] ?? 'مرفق') ?></span>
                                        <strong><?= e($attachment['original_name']) ?></strong>
                                        <small><?= numberAr(((int)$attachment['file_size']) / 1024, 1) ?> ك.ب · تنزيل آمن</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <?php if ($selectedApplication['status'] === 'accepted' && $selectedApplication['employee_user_id'] === null): ?>
                        <article class="panel employment-provision-panel">
                            <header class="employment-section-heading">
                                <div><span>الخطوة الأخيرة</span><h3>إنشاء حساب وبطاقة الموظف</h3></div>
                            </header>
                            <div class="employment-inline-notice">لن يُنشأ الحساب إلا مرة واحدة. تُحفظ كلمة المرور مشفّرة، وتظهر قيمتها المدخلة مرة واحدة فقط بعد نجاح العملية.</div>
                            <form method="post" class="employment-form-grid" autocomplete="off" data-confirm="سيتم إنشاء حساب موظف وربطه نهائيًا بهذا الطلب. هل راجعت بيانات التعيين؟">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="provision_account">
                                <input type="hidden" name="application_id" value="<?= (int)$selectedApplication['id'] ?>">
                                <label>اسم المستخدم <b>*</b>
                                    <input name="username" value="<?= e($suggestedUsername) ?>" minlength="4" maxlength="100" pattern="[A-Za-z0-9._-]{4,100}" dir="ltr" required autocomplete="off">
                                </label>
                                <label>كلمة المرور المؤقتة <b>*</b>
                                    <input type="password" name="password" minlength="10" maxlength="200" required autocomplete="new-password" placeholder="10 محارف على الأقل" dir="ltr">
                                </label>
                                <label>الرقم الوظيفي <b>*</b>
                                    <input name="employee_number" value="<?= e($suggestedEmployeeNumber) ?>" maxlength="40" required dir="ltr">
                                </label>
                                <label>تاريخ المباشرة <b>*</b>
                                    <input type="date" name="hire_date" value="<?= date('Y-m-d') ?>" required>
                                </label>
                                <label>نوع العقد <b>*</b>
                                    <select name="contract_type" required>
                                        <option value="permanent">دائم</option>
                                        <option value="temporary">مؤقت</option>
                                    </select>
                                </label>
                                <label>نهاية العقد المؤقت
                                    <input type="date" name="contract_end_date">
                                </label>
                                <label>الراتب الأساسي
                                    <input type="number" name="base_salary" min="0" max="99999999.99" step="0.01" value="<?= e((string)($selectedApplication['job_salary_min'] ?? '0')) ?>">
                                </label>
                                <label>المسمى الوظيفي <b>*</b>
                                    <input name="job_title" value="<?= e($selectedApplication['job_title']) ?>" maxlength="190" required>
                                </label>
                                <label>الإدارة أو القسم
                                    <input name="department" value="<?= e($selectedApplication['job_department']) ?>" maxlength="190">
                                </label>
                                <label>المرتبة / الدرجة
                                    <input name="job_grade" maxlength="80">
                                </label>
                                <label>اسم المشرف المباشر
                                    <input name="supervisor_name" maxlength="190">
                                </label>
                                <label>جوال المشرف
                                    <input name="supervisor_phone" maxlength="30" dir="ltr">
                                </label>
                                <label>ميناء التكليف الأول
                                    <select name="selected_port_id">
                                        <option value="">دون تكليف الآن</option>
                                        <?php foreach ($ports as $port): ?>
                                            <option value="<?= (int)$port['id'] ?>"><?= e($port['governorate_name'] . ' — ' . $port['name'] . ($suggestedPortId === (int)$port['id'] ? ' (المقترح)' : '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>المناوبة الأولى
                                    <select name="selected_shift_id">
                                        <option value="">دون مناوبة الآن</option>
                                        <?php foreach ($shifts as $shift): ?>
                                            <option value="<?= (int)$shift['id'] ?>"><?= e(($shiftLabels[$shift['name']] ?? $shift['name']) . ' · ' . substr($shift['start_time'], 0, 5) . '–' . substr($shift['end_time'], 0, 5)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <p class="span-2 employment-form-hint">يُنشأ التكليف الأول فقط عند اختيار الميناء والمناوبة معًا، ويبدأ في تاريخ المباشرة.</p>
                                <div class="span-2 employment-form-actions"><button class="btn btn-primary" type="submit">إنشاء حساب الموظف</button></div>
                            </form>
                        </article>
                    <?php elseif ($selectedApplication['status'] === 'account_created'): ?>
                        <article class="panel employment-account-linked">
                            <span>اكتمل التعيين</span>
                            <h3>الحساب مرتبط بهذا الطلب</h3>
                            <p>اسم المستخدم: <strong dir="ltr"><?= e($selectedApplication['linked_username'] ?: '—') ?></strong>. كلمة المرور غير قابلة للعرض أو الاسترجاع من النظام.</p>
                        </article>
                    <?php endif; ?>
                </div>

                <aside class="employment-detail-side">
                    <article class="panel employment-review-card">
                        <span class="employment-eyebrow">قرار المراجعة</span>
                        <h3><?= e($statusLabels[$selectedApplication['status']]) ?></h3>
                        <?php if ($selectedApplication['status'] === 'account_created'): ?>
                            <p>أُغلق مسار المراجعة بعد إنشاء حساب الموظف.</p>
                        <?php elseif ($selectedApplication['status'] === 'withdrawn'): ?>
                            <p>سحب المتقدم الطلب، لذلك لا يمكن تغيير حالته.</p>
                        <?php else: ?>
                            <form method="post" class="employment-review-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="save_review">
                                <input type="hidden" name="application_id" value="<?= (int)$selectedApplication['id'] ?>">
                                <label>الحالة التالية
                                    <select name="status">
                                        <option value="<?= e($selectedApplication['status']) ?>"><?= e($statusLabels[$selectedApplication['status']]) ?> — دون تغيير</option>
                                        <?php foreach ($allowedTransitions[$selectedApplication['status']] ?? [] as $nextStatus): ?>
                                            <option value="<?= e($nextStatus) ?>"><?= e($statusLabels[$nextStatus]) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>ملاحظة داخلية
                                    <textarea name="admin_note" rows="7" maxlength="5000" placeholder="أسباب القرار أو نقاط المتابعة"><?= e($selectedApplication['admin_note']) ?></textarea>
                                </label>
                                <button class="btn btn-primary" type="submit">حفظ المراجعة</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($selectedApplication['reviewer_name']): ?>
                            <small>آخر مراجعة: <?= e($selectedApplication['reviewer_name']) ?> · <?= e($selectedApplication['reviewed_at']) ?></small>
                        <?php endif; ?>
                    </article>

                    <article class="panel employment-timeline-card">
                        <span class="employment-eyebrow">سجل الحركة</span>
                        <h3>خط زمني غير قابل للمحو</h3>
                        <?php if (!$events): ?>
                            <p class="employment-muted">لا توجد أحداث مسجلة.</p>
                        <?php else: ?>
                            <ol class="employment-timeline">
                                <?php foreach ($events as $event): ?>
                                    <li>
                                        <strong><?= e($eventLabels[$event['event_type']] ?? $event['event_type']) ?></strong>
                                        <?php if ($event['from_status'] !== $event['to_status'] && $event['to_status']): ?>
                                            <span><?= e($statusLabels[$event['from_status']] ?? 'البداية') ?> ← <?= e($statusLabels[$event['to_status']] ?? $event['to_status']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($event['note']): ?><p><?= nl2br(e($event['note'])) ?></p><?php endif; ?>
                                        <small><?= e($event['actor_name'] ?: 'المتقدم') ?> · <?= e($event['created_at']) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </article>
                </aside>
            </div>
        </section>
    <?php elseif ($selectedApplicationId > 0): ?>
        <section class="panel employment-empty-state"><strong>الطلب غير موجود</strong><p>ربما حُذف أو أن الرابط غير صحيح.</p></section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
