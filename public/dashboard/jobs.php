<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'hr_manager']);
$pageTitle = 'إدارة فرص التوظيف';
$activeRoute = 'jobs.php';
$pageStyles = ['css/employment.css'];

$pdo = db();
$jobsUrl = BASE_URL . '/dashboard/jobs.php';

$statusLabels = [
    'draft' => 'مسودة',
    'open' => 'متاحة للتقديم',
    'closed' => 'مغلقة',
    'archived' => 'مؤرشفة',
];
$employmentTypeLabels = [
    'full_time' => 'دوام كامل',
    'part_time' => 'دوام جزئي',
    'temporary' => 'عمل مؤقت',
    'contract' => 'عقد',
];

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

$jobExists = static function (PDO $pdo, int $jobId): ?array {
    $stmt = $pdo->prepare('SELECT * FROM employment_jobs WHERE id = ? LIMIT 1');
    $stmt->execute([$jobId]);
    $row = $stmt->fetch();
    return $row ?: null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = max(0, (int)($_POST['job_id'] ?? 0));
    $returnUrl = $jobsUrl;
    if (($_POST['action'] ?? '') === 'save_job') {
        $returnUrl .= $jobId > 0 ? '?edit=' . $jobId : '?new=1';
    }

    if (!verifyCsrf()) {
        redirectWithMessage($returnUrl, 'error', 'انتهت صلاحية الجلسة. أعد المحاولة.');
    }

    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'save_job') {
            $title = trim((string)($_POST['title_ar'] ?? ''));
            $department = trim((string)($_POST['department'] ?? ''));
            $summary = trim((string)($_POST['summary'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $responsibilities = trim((string)($_POST['responsibilities'] ?? ''));
            $requirements = trim((string)($_POST['requirements'] ?? ''));
            $employmentType = (string)($_POST['employment_type'] ?? 'full_time');
            $vacancies = (int)($_POST['vacancies'] ?? 1);
            $portId = max(0, (int)($_POST['port_id'] ?? 0));
            $city = trim((string)($_POST['city'] ?? ''));
            $salaryMinRaw = trim((string)($_POST['salary_min'] ?? ''));
            $salaryMaxRaw = trim((string)($_POST['salary_max'] ?? ''));
            $deadline = trim((string)($_POST['application_deadline'] ?? ''));

            $errors = [];
            if ($title === '' || mb_strlen($title) > 190) {
                $errors[] = 'أدخل مسمى وظيفيًا صحيحًا لا يتجاوز 190 حرفًا.';
            }
            if (mb_strlen($department) > 190) {
                $errors[] = 'اسم الإدارة أو القسم أطول من الحد المسموح.';
            }
            if ($summary === '' || $description === '' || $requirements === '') {
                $errors[] = 'الملخص والوصف والمتطلبات حقول إلزامية.';
            }
            if (!isset($employmentTypeLabels[$employmentType])) {
                $errors[] = 'نوع التوظيف غير صالح.';
            }
            if ($vacancies < 1 || $vacancies > 65535) {
                $errors[] = 'عدد الشواغر يجب أن يكون بين 1 و65535.';
            }
            if ($city !== '' && mb_strlen($city) > 120) {
                $errors[] = 'اسم المدينة أطول من الحد المسموح.';
            }
            if (!$isValidDate($deadline)) {
                $errors[] = 'تاريخ إغلاق التقديم غير صالح.';
            }

            $salaryMin = null;
            $salaryMax = null;
            if ($salaryMinRaw !== '') {
                if (!is_numeric($salaryMinRaw) || (float)$salaryMinRaw < 0 || (float)$salaryMinRaw > 99999999.99) {
                    $errors[] = 'الحد الأدنى للراتب غير صالح.';
                } else {
                    $salaryMin = round((float)$salaryMinRaw, 2);
                }
            }
            if ($salaryMaxRaw !== '') {
                if (!is_numeric($salaryMaxRaw) || (float)$salaryMaxRaw < 0 || (float)$salaryMaxRaw > 99999999.99) {
                    $errors[] = 'الحد الأعلى للراتب غير صالح.';
                } else {
                    $salaryMax = round((float)$salaryMaxRaw, 2);
                }
            }
            if ($salaryMin !== null && $salaryMax !== null && $salaryMin > $salaryMax) {
                $errors[] = 'الحد الأعلى للراتب يجب ألا يقل عن الحد الأدنى.';
            }

            if ($portId > 0) {
                $portStmt = $pdo->prepare('SELECT id FROM ports WHERE id = ? LIMIT 1');
                $portStmt->execute([$portId]);
                if (!$portStmt->fetchColumn()) {
                    $errors[] = 'الميناء المختار غير موجود.';
                }
            }

            $existingJob = null;
            if ($jobId > 0) {
                $existingJob = $jobExists($pdo, $jobId);
                if (!$existingJob) {
                    $errors[] = 'الفرصة الوظيفية المطلوبة غير موجودة.';
                } elseif ($existingJob['status'] === 'archived') {
                    $errors[] = 'استعد الفرصة من الأرشيف قبل تعديل بياناتها.';
                }
            }

            if ($errors) {
                redirectWithMessage($returnUrl, 'error', implode(' ', $errors));
            }

            $values = [
                $title,
                $department !== '' ? $department : null,
                $summary,
                $description,
                $responsibilities !== '' ? $responsibilities : null,
                $requirements,
                $employmentType,
                $vacancies,
                $portId > 0 ? $portId : null,
                $city !== '' ? $city : null,
                $salaryMin,
                $salaryMax,
                $deadline !== '' ? $deadline : null,
            ];

            if ($existingJob) {
                $values[] = $currentUserData['id'];
                $values[] = $jobId;
                $pdo->prepare(
                    'UPDATE employment_jobs
                     SET title_ar = ?, department = ?, summary = ?, description = ?, responsibilities = ?,
                         requirements = ?, employment_type = ?, vacancies = ?, port_id = ?, city = ?,
                         salary_min = ?, salary_max = ?, application_deadline = ?, updated_by = ?
                     WHERE id = ?'
                )->execute($values);
                redirectWithMessage($jobsUrl . '?edit=' . $jobId, 'success', 'تم حفظ تعديلات الفرصة الوظيفية.');
            }

            do {
                $reference = 'JOB-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $refStmt = $pdo->prepare('SELECT 1 FROM employment_jobs WHERE reference_no = ?');
                $refStmt->execute([$reference]);
            } while ($refStmt->fetchColumn());

            array_unshift($values, $reference);
            $values[] = $currentUserData['id'];
            $pdo->prepare(
                "INSERT INTO employment_jobs
                    (reference_no, title_ar, department, summary, description, responsibilities, requirements,
                     employment_type, vacancies, port_id, city, salary_min, salary_max, application_deadline, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute($values);
            $newJobId = (int)$pdo->lastInsertId();
            redirectWithMessage($jobsUrl . '?edit=' . $newJobId, 'success', 'تم إنشاء مسودة الفرصة الوظيفية. راجعها ثم انشرها عند الجاهزية.');
        }

        if (!in_array($action, ['publish_job', 'close_job', 'archive_job', 'restore_job'], true) || $jobId < 1) {
            redirectWithMessage($jobsUrl, 'error', 'الإجراء المطلوب غير صالح.');
        }

        $job = $jobExists($pdo, $jobId);
        if (!$job) {
            redirectWithMessage($jobsUrl, 'error', 'الفرصة الوظيفية المطلوبة غير موجودة.');
        }

        if ($action === 'publish_job') {
            if (!in_array($job['status'], ['draft', 'closed'], true)) {
                redirectWithMessage($jobsUrl, 'error', 'لا يمكن نشر هذه الفرصة من حالتها الحالية.');
            }
            if ($job['application_deadline'] !== null && $job['application_deadline'] < date('Y-m-d')) {
                redirectWithMessage($jobsUrl . '?edit=' . $jobId, 'error', 'حدّث موعد إغلاق التقديم قبل نشر الفرصة.');
            }
            $stmt = $pdo->prepare(
                "UPDATE employment_jobs
                 SET status = 'open', published_at = COALESCE(published_at, NOW()), updated_by = ?
                 WHERE id = ? AND status IN ('draft', 'closed')"
            );
            $stmt->execute([$currentUserData['id'], $jobId]);
            redirectWithMessage($jobsUrl, 'success', 'أصبحت الفرصة متاحة للتقديم في صفحة الوظائف العامة.');
        }

        if ($action === 'close_job') {
            $stmt = $pdo->prepare(
                "UPDATE employment_jobs SET status = 'closed', updated_by = ? WHERE id = ? AND status = 'open'"
            );
            $stmt->execute([$currentUserData['id'], $jobId]);
            if ($stmt->rowCount() !== 1) {
                redirectWithMessage($jobsUrl, 'error', 'يمكن إغلاق الفرص المتاحة فقط.');
            }
            redirectWithMessage($jobsUrl, 'success', 'تم إغلاق التقديم على الفرصة.');
        }

        if ($action === 'archive_job') {
            $stmt = $pdo->prepare(
                "UPDATE employment_jobs SET status = 'archived', updated_by = ? WHERE id = ? AND status <> 'archived'"
            );
            $stmt->execute([$currentUserData['id'], $jobId]);
            if ($stmt->rowCount() !== 1) {
                redirectWithMessage($jobsUrl, 'error', 'الفرصة مؤرشفة بالفعل.');
            }
            redirectWithMessage($jobsUrl, 'success', 'تم نقل الفرصة إلى الأرشيف.');
        }

        $stmt = $pdo->prepare(
            "UPDATE employment_jobs SET status = 'draft', updated_by = ? WHERE id = ? AND status = 'archived'"
        );
        $stmt->execute([$currentUserData['id'], $jobId]);
        if ($stmt->rowCount() !== 1) {
            redirectWithMessage($jobsUrl, 'error', 'يمكن استعادة الفرص المؤرشفة فقط.');
        }
        redirectWithMessage($jobsUrl . '?edit=' . $jobId, 'success', 'تمت استعادة الفرصة كمسودة.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Employment jobs error: ' . $e->getMessage());
        redirectWithMessage($returnUrl, 'error', 'تعذر تنفيذ الإجراء الآن. تحقق من البيانات ثم أعد المحاولة.');
    }
}

$ports = $pdo->query(
    'SELECT p.id, p.name, g.name AS governorate_name
     FROM ports p JOIN governorates g ON g.id = p.governorate_id
     WHERE p.is_active = 1 ORDER BY g.name, p.name'
)->fetchAll();

$statsRow = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'open') AS open_count,
            SUM(status = 'draft') AS draft_count,
            SUM(status = 'closed') AS closed_count,
            SUM(status = 'archived') AS archived_count
     FROM employment_jobs"
)->fetch() ?: [];
$stats = [
    'total' => (int)($statsRow['total'] ?? 0),
    'open' => (int)($statsRow['open_count'] ?? 0),
    'draft' => (int)($statsRow['draft_count'] ?? 0),
    'closed' => (int)($statsRow['closed_count'] ?? 0),
    'archived' => (int)($statsRow['archived_count'] ?? 0),
];

$statusFilter = (string)($_GET['status'] ?? 'all');
if ($statusFilter !== 'all' && !isset($statusLabels[$statusFilter])) {
    $statusFilter = 'all';
}
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$where = [];
$params = [];
if ($statusFilter !== 'all') {
    $where[] = 'j.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(j.reference_no LIKE ? OR j.title_ar LIKE ? OR j.department LIKE ? OR j.city LIKE ?)';
    $needle = '%' . $search . '%';
    array_push($params, $needle, $needle, $needle, $needle);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$jobsStmt = $pdo->prepare(
    "SELECT j.*, p.name AS port_name,
            (SELECT COUNT(*) FROM employment_applications a WHERE a.job_id = j.id) AS applications_count,
            (SELECT COUNT(*) FROM employment_applications a
             WHERE a.job_id = j.id AND a.status IN ('accepted', 'account_created')) AS accepted_count
     FROM employment_jobs j
     LEFT JOIN ports p ON p.id = j.port_id
     $whereSql
     ORDER BY FIELD(j.status, 'open', 'draft', 'closed', 'archived'), j.created_at DESC"
);
$jobsStmt->execute($params);
$jobs = $jobsStmt->fetchAll();

$formJob = null;
$formMode = null;
if (isset($_GET['new'])) {
    $formMode = 'create';
    $formJob = [
        'id' => 0,
        'reference_no' => '',
        'title_ar' => '',
        'department' => '',
        'summary' => '',
        'description' => '',
        'responsibilities' => '',
        'requirements' => '',
        'employment_type' => 'full_time',
        'vacancies' => 1,
        'port_id' => '',
        'city' => '',
        'salary_min' => '',
        'salary_max' => '',
        'application_deadline' => '',
        'status' => 'draft',
    ];
} elseif (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $formJob = $jobExists($pdo, (int)$_GET['edit']);
    if ($formJob) {
        $formMode = 'edit';
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="employment-admin-shell">
    <section class="employment-admin-hero">
        <div>
            <span class="employment-eyebrow">مركز الاستقطاب</span>
            <h2>من المسودة إلى التعيين، في مسار واضح</h2>
            <p>أنشئ الفرص، تحكم في فترة استقبال الطلبات، وانتقل مباشرة إلى ملفات المتقدمين.</p>
        </div>
        <div class="employment-hero-actions">
            <a class="btn btn-primary" href="<?= e($jobsUrl) ?>?new=1">إضافة فرصة جديدة</a>
            <a class="btn btn-outline" href="<?= e(BASE_URL) ?>/dashboard/job_applications.php">طلبات التوظيف</a>
            <a class="btn btn-outline" href="<?= e(BASE_URL) ?>/jobs.php" target="_blank" rel="noopener">عرض الصفحة العامة</a>
        </div>
    </section>

    <nav class="employment-workflow-nav" aria-label="التوظيف">
        <a class="active" href="<?= e($jobsUrl) ?>">الفرص الوظيفية</a>
        <a href="<?= e(BASE_URL) ?>/dashboard/job_applications.php">طلبات المتقدمين</a>
    </nav>

    <section class="employment-kpis" aria-label="مؤشرات الفرص">
        <article><span>جميع الفرص</span><strong><?= numberAr($stats['total']) ?></strong></article>
        <article class="tone-live"><span>متاحة الآن</span><strong><?= numberAr($stats['open']) ?></strong></article>
        <article><span>مسودات</span><strong><?= numberAr($stats['draft']) ?></strong></article>
        <article><span>مغلقة</span><strong><?= numberAr($stats['closed']) ?></strong></article>
        <article><span>الأرشيف</span><strong><?= numberAr($stats['archived']) ?></strong></article>
    </section>

    <?php if ($formJob): ?>
        <section class="panel employment-editor" id="job-editor">
            <header class="employment-section-heading">
                <div>
                    <span><?= $formMode === 'create' ? 'فرصة جديدة' : e($formJob['reference_no']) ?></span>
                    <h3><?= $formMode === 'create' ? 'صياغة إعلان وظيفي' : 'تعديل ' . e($formJob['title_ar']) ?></h3>
                </div>
                <a class="btn btn-outline btn-sm" href="<?= e($jobsUrl) ?>">إغلاق المحرر</a>
            </header>

            <?php if ($formJob['status'] === 'archived'): ?>
                <div class="employment-inline-notice">هذه الفرصة في الأرشيف. استعدها كمسودة قبل تعديل بياناتها.</div>
            <?php else: ?>
                <form method="post" class="employment-form-grid">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save_job">
                    <input type="hidden" name="job_id" value="<?= (int)$formJob['id'] ?>">

                    <label class="span-2">المسمى الوظيفي <b>*</b>
                        <input name="title_ar" required maxlength="190" value="<?= e($formJob['title_ar']) ?>" placeholder="مثال: أخصائي إحصاء مصايد">
                    </label>
                    <label>الإدارة أو القسم
                        <input name="department" maxlength="190" value="<?= e($formJob['department']) ?>" placeholder="الموارد والبيانات">
                    </label>
                    <label>نوع التوظيف <b>*</b>
                        <select name="employment_type" required>
                            <?php foreach ($employmentTypeLabels as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $formJob['employment_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>عدد الشواغر <b>*</b>
                        <input type="number" name="vacancies" min="1" max="65535" required value="<?= (int)$formJob['vacancies'] ?>">
                    </label>
                    <label>الميناء المرتبط
                        <select name="port_id">
                            <option value="">غير محدد</option>
                            <?php foreach ($ports as $port): ?>
                                <option value="<?= (int)$port['id'] ?>" <?= (int)$formJob['port_id'] === (int)$port['id'] ? 'selected' : '' ?>>
                                    <?= e($port['governorate_name'] . ' — ' . $port['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>المدينة
                        <input name="city" maxlength="120" value="<?= e($formJob['city']) ?>">
                    </label>
                    <label>آخر موعد للتقديم
                        <input type="date" name="application_deadline" value="<?= e($formJob['application_deadline']) ?>">
                    </label>
                    <label>الراتب من
                        <input type="number" name="salary_min" min="0" max="99999999.99" step="0.01" value="<?= e((string)$formJob['salary_min']) ?>">
                    </label>
                    <label>الراتب إلى
                        <input type="number" name="salary_max" min="0" max="99999999.99" step="0.01" value="<?= e((string)$formJob['salary_max']) ?>">
                    </label>
                    <label class="span-2">ملخص الإعلان <b>*</b>
                        <textarea name="summary" rows="3" required placeholder="فقرة موجزة تظهر في بطاقة الفرصة العامة"><?= e($formJob['summary']) ?></textarea>
                    </label>
                    <label class="span-2">الوصف الوظيفي <b>*</b>
                        <textarea name="description" rows="6" required><?= e($formJob['description']) ?></textarea>
                    </label>
                    <label class="span-2">المسؤوليات والمهام
                        <textarea name="responsibilities" rows="6" placeholder="اكتب كل مسؤولية في سطر مستقل"><?= e($formJob['responsibilities']) ?></textarea>
                    </label>
                    <label class="span-2">المؤهلات والمتطلبات <b>*</b>
                        <textarea name="requirements" rows="6" required placeholder="اكتب كل متطلب في سطر مستقل"><?= e($formJob['requirements']) ?></textarea>
                    </label>
                    <div class="span-2 employment-form-actions">
                        <button class="btn btn-primary" type="submit">حفظ البيانات</button>
                        <a class="btn btn-outline" href="<?= e($jobsUrl) ?>">إلغاء</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="panel employment-list-panel">
        <header class="employment-section-heading">
            <div><span>سجل الفرص</span><h3>الإعلانات وحالات النشر</h3></div>
        </header>

        <form method="get" class="employment-filter-bar">
            <label class="employment-search-field">بحث
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="المسمى، الرقم المرجعي، القسم أو المدينة">
            </label>
            <label>الحالة
                <select name="status">
                    <option value="all">كل الحالات</option>
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-outline" type="submit">تصفية</button>
            <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                <a class="btn btn-outline" href="<?= e($jobsUrl) ?>">مسح</a>
            <?php endif; ?>
        </form>

        <?php if (!$jobs): ?>
            <div class="employment-empty-state">
                <strong>لا توجد فرص مطابقة</strong>
                <p>أنشئ أول مسودة أو غيّر مرشحات البحث.</p>
            </div>
        <?php else: ?>
            <div class="employment-job-admin-list">
                <?php foreach ($jobs as $job): ?>
                    <article class="employment-job-admin-card">
                        <div class="employment-job-main">
                            <div class="employment-card-topline">
                                <span class="employment-status status-<?= e($job['status']) ?>"><?= e($statusLabels[$job['status']]) ?></span>
                                <code><?= e($job['reference_no']) ?></code>
                            </div>
                            <h4><?= e($job['title_ar']) ?></h4>
                            <p><?= e($job['department'] ?: 'غير محدد') ?><?= $job['city'] ? ' · ' . e($job['city']) : '' ?><?= $job['port_name'] ? ' · ' . e($job['port_name']) : '' ?></p>
                            <div class="employment-job-meta">
                                <span><?= e($employmentTypeLabels[$job['employment_type']]) ?></span>
                                <span><?= numberAr($job['vacancies']) ?> شاغر</span>
                                <span><?= numberAr($job['applications_count']) ?> طلب</span>
                                <span><?= numberAr($job['accepted_count']) ?> مقبول</span>
                                <span>الإغلاق: <?= e($job['application_deadline'] ?: 'مفتوح') ?></span>
                            </div>
                        </div>
                        <div class="employment-card-actions">
                            <a class="btn btn-outline btn-sm" href="<?= e($jobsUrl) ?>?edit=<?= (int)$job['id'] ?>#job-editor">تعديل</a>
                            <a class="btn btn-outline btn-sm" href="<?= e(BASE_URL) ?>/dashboard/job_applications.php?job_id=<?= (int)$job['id'] ?>">الطلبات</a>
                            <?php if ($job['status'] === 'open'): ?>
                                <a class="btn btn-outline btn-sm" href="<?= e(BASE_URL) ?>/job.php?id=<?= (int)$job['id'] ?>" target="_blank" rel="noopener">معاينة</a>
                                <form method="post" data-confirm="سيتم إيقاف استقبال الطلبات الجديدة. هل تريد المتابعة؟">
                                    <?= csrfField() ?><input type="hidden" name="action" value="close_job"><input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                    <button class="btn btn-outline btn-sm" type="submit">إغلاق التقديم</button>
                                </form>
                            <?php elseif (in_array($job['status'], ['draft', 'closed'], true)): ?>
                                <form method="post">
                                    <?= csrfField() ?><input type="hidden" name="action" value="publish_job"><input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                    <button class="btn btn-primary btn-sm" type="submit"><?= $job['status'] === 'closed' ? 'إعادة الفتح' : 'نشر الفرصة' ?></button>
                                </form>
                            <?php endif; ?>

                            <?php if ($job['status'] === 'archived'): ?>
                                <form method="post">
                                    <?= csrfField() ?><input type="hidden" name="action" value="restore_job"><input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                    <button class="btn btn-outline btn-sm" type="submit">استعادة كمسودة</button>
                                </form>
                            <?php else: ?>
                                <form method="post" data-confirm="سيتم إخفاء هذه الفرصة ونقلها إلى الأرشيف. هل تريد المتابعة؟">
                                    <?= csrfField() ?><input type="hidden" name="action" value="archive_job"><input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                    <button class="btn btn-outline btn-sm" type="submit">أرشفة</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
