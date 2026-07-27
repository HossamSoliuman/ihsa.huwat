<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/employment_public_functions.php';

$pageTitle = 'الوظائف المتاحة';
$pageDescription = 'استعرض الفرص الوظيفية المفتوحة في الموانئ وقطاع الإحصاء وقدّم طلبك إلكترونياً.';
$activePublicRoute = 'jobs';
$bodyClass = 'employment-jobs-page';

$getString = static function (string $key): string {
    $value = $_GET[$key] ?? '';
    return is_scalar($value) ? trim((string)$value) : '';
};

$query = $getString('q');
$query = mb_substr($query, 0, 100);
$department = $getString('department');
$department = mb_substr($department, 0, 150);
$employmentType = $getString('type');
$allowedTypes = array_keys(employmentPublicTypeLabels());
if (!in_array($employmentType, $allowedTypes, true)) {
    $employmentType = '';
}

$jobs = [];
$departments = [];
$loadError = null;

try {
    $pdo = db();
    $availability = "j.status = 'open'
        AND (j.published_at IS NULL OR j.published_at <= NOW())
        AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())";

    $departmentStatement = $pdo->query(
        "SELECT DISTINCT j.department
         FROM employment_jobs j
         WHERE {$availability} AND j.department IS NOT NULL AND j.department <> ''
         ORDER BY j.department"
    );
    $departments = $departmentStatement->fetchAll(PDO::FETCH_COLUMN);

    $where = [$availability];
    $parameters = [];

    if ($query !== '') {
        $where[] = '(j.title_ar LIKE :query_title OR j.department LIKE :query_department OR j.summary LIKE :query_summary OR j.city LIKE :query_city OR p.name LIKE :query_port)';
        $search = '%' . $query . '%';
        $parameters['query_title'] = $search;
        $parameters['query_department'] = $search;
        $parameters['query_summary'] = $search;
        $parameters['query_city'] = $search;
        $parameters['query_port'] = $search;
    }

    if ($department !== '') {
        $where[] = 'j.department = :department';
        $parameters['department'] = $department;
    }

    if ($employmentType !== '') {
        $where[] = 'j.employment_type = :employment_type';
        $parameters['employment_type'] = $employmentType;
    }

    $statement = $pdo->prepare(
        'SELECT j.*, p.name AS port_name
         FROM employment_jobs j
         LEFT JOIN ports p ON p.id = j.port_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY COALESCE(j.published_at, j.created_at) DESC, j.id DESC'
    );
    $statement->execute($parameters);
    $jobs = $statement->fetchAll();
} catch (Throwable $exception) {
    error_log('Public employment jobs error: ' . $exception->getMessage());
    $loadError = 'تعذر تحميل الوظائف في الوقت الحالي. يرجى المحاولة مرة أخرى لاحقاً.';
}

require BASE_PATH . '/includes/public_employment_header.php';
?>

<section class="employment-hero">
    <div class="employment-container employment-hero-grid">
        <div class="employment-hero-copy">
            <span class="employment-eyebrow">مسارك المهني يبدأ من هنا</span>
            <h1>اعمل حيث تلتقي <em>البيانات</em> بالبحر</h1>
            <p>انضم إلى فرق تسهم في تطوير الموانئ ورفع جودة إحصاءات المصيد. اختر الفرصة المناسبة، وأرسل طلبك عبر خطوات واضحة وآمنة.</p>
            <a class="employment-button employment-button-primary" href="#available-jobs">
                استعرض الفرص
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </a>
        </div>
        <div class="employment-hero-visual" aria-hidden="true">
            <span class="employment-orbit employment-orbit-one"></span>
            <span class="employment-orbit employment-orbit-two"></span>
            <svg viewBox="0 0 320 260">
                <path class="employment-hero-water" d="M16 200c38-20 65 20 104 0s67-20 106 0 57 12 78 0"></path>
                <path class="employment-hero-anchor" d="M160 30v145M124 74h72M94 131c0 59 30 94 66 94s66-35 66-94M67 144h51M202 144h51"></path>
                <circle class="employment-hero-anchor" cx="160" cy="42" r="18"></circle>
                <path class="employment-hero-accent" d="M110 229c16-13 33-13 50 0s34 13 51 0"></path>
            </svg>
            <span class="employment-hero-note"><b><?= count($jobs) ?></b> فرصة متاحة الآن</span>
        </div>
    </div>
</section>

<section class="employment-section" id="available-jobs" aria-labelledby="jobs-heading">
    <div class="employment-container">
        <div class="employment-section-heading">
            <div>
                <span class="employment-eyebrow">فرصنا الحالية</span>
                <h2 id="jobs-heading">ابحث عن دورك القادم</h2>
            </div>
            <?php if (!$loadError): ?>
                <p class="employment-result-count" aria-live="polite"><?= count($jobs) ?> نتيجة</p>
            <?php endif; ?>
        </div>

        <form class="employment-job-filters" method="get" action="<?= e(BASE_URL . '/jobs.php') ?>" role="search">
            <div class="employment-search-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                <label class="sr-only" for="job-search">ابحث في الوظائف</label>
                <input id="job-search" type="search" name="q" value="<?= e($query) ?>" placeholder="المسمى أو الإدارة أو المدينة">
            </div>
            <div>
                <label class="sr-only" for="department-filter">الإدارة</label>
                <select id="department-filter" name="department">
                    <option value="">كل الإدارات</option>
                    <?php foreach ($departments as $departmentOption): ?>
                        <option value="<?= e((string)$departmentOption) ?>"<?= $department === (string)$departmentOption ? ' selected' : '' ?>><?= e((string)$departmentOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="sr-only" for="type-filter">نوع الدوام</label>
                <select id="type-filter" name="type">
                    <option value="">كل أنواع الدوام</option>
                    <?php foreach (employmentPublicTypeLabels() as $typeValue => $typeLabel): ?>
                        <option value="<?= e($typeValue) ?>"<?= $employmentType === $typeValue ? ' selected' : '' ?>><?= e($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="employment-button employment-button-primary" type="submit">بحث</button>
            <?php if ($query !== '' || $department !== '' || $employmentType !== ''): ?>
                <a class="employment-filter-reset" href="<?= e(BASE_URL . '/jobs.php#available-jobs') ?>">مسح الفلاتر</a>
            <?php endif; ?>
        </form>

        <?php if ($loadError): ?>
            <div class="employment-alert employment-alert-error" role="alert">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"></path><path d="m10.3 3.8-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0Z"></path></svg>
                <p><?= e($loadError) ?></p>
            </div>
        <?php elseif (!$jobs): ?>
            <div class="employment-empty-state">
                <span aria-hidden="true">
                    <svg viewBox="0 0 72 72"><path d="M17 23h38v34H17zM26 23v-7h20v7M17 35h38M31 35v5h10v-5"></path></svg>
                </span>
                <h3>لا توجد فرص مطابقة حالياً</h3>
                <p>جرّب تغيير كلمات البحث أو الفلاتر، أو عد لاحقاً للاطلاع على الفرص الجديدة.</p>
                <a class="employment-button employment-button-outline" href="<?= e(BASE_URL . '/jobs.php#available-jobs') ?>">عرض كل الوظائف</a>
            </div>
        <?php else: ?>
            <div class="employment-job-grid">
                <?php foreach ($jobs as $job): ?>
                    <?php
                    $location = trim(implode('، ', array_filter([(string)($job['port_name'] ?? ''), (string)($job['city'] ?? '')])));
                    $deadline = $job['application_deadline'] ? new DateTimeImmutable((string)$job['application_deadline']) : null;
                    $today = new DateTimeImmutable('today');
                    $daysRemaining = $deadline ? (int)$today->diff($deadline)->format('%r%a') : null;
                    ?>
                    <article class="employment-job-card">
                        <div class="employment-job-card-topline">
                            <span class="employment-job-reference mono"><?= e((string)$job['reference_no']) ?></span>
                            <span class="employment-job-type"><?= e(employmentPublicTypeLabel((string)$job['employment_type'])) ?></span>
                        </div>
                        <div class="employment-job-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM8 7V4h8v3M4 12h16M10 12v2h4v-2"></path></svg>
                        </div>
                        <h3><a href="<?= e(BASE_URL . '/job.php?id=' . (int)$job['id']) ?>"><?= e((string)$job['title_ar']) ?></a></h3>
                        <?php if (!empty($job['department'])): ?><p class="employment-job-department"><?= e((string)$job['department']) ?></p><?php endif; ?>
                        <p class="employment-job-summary"><?= e(employmentPublicExcerpt((string)($job['summary'] ?: $job['description']))) ?></p>
                        <dl class="employment-job-meta">
                            <?php if ($location !== ''): ?>
                                <div><dt><span class="sr-only">الموقع</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></dt><dd><?= e($location) ?></dd></div>
                            <?php endif; ?>
                            <div><dt><span class="sr-only">الراتب</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M15.5 8.5c-.8-1-2-1.5-3.5-1.5-2 0-3.5 1.1-3.5 2.7s1.3 2.3 3.5 2.8 3.5 1.2 3.5 2.8S14 18 12 18c-1.7 0-3-.5-3.8-1.5M12 5v14"></path></svg></dt><dd><?= e(employmentPublicSalary($job['salary_min'], $job['salary_max'])) ?></dd></div>
                            <div><dt><span class="sr-only">آخر موعد</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></dt><dd><?= $deadline ? ($daysRemaining === 0 ? 'ينتهي اليوم' : 'حتى ' . e(employmentPublicDate((string)$job['application_deadline']))) : 'التقديم مفتوح' ?></dd></div>
                        </dl>
                        <div class="employment-job-card-actions">
                            <a class="employment-button employment-button-primary" href="<?= e(BASE_URL . '/apply.php?job_id=' . (int)$job['id']) ?>">تقدّم الآن</a>
                            <a class="employment-text-link" href="<?= e(BASE_URL . '/job.php?id=' . (int)$job['id']) ?>">التفاصيل <span aria-hidden="true">←</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="employment-process" id="application-process" aria-labelledby="process-heading">
    <div class="employment-container">
        <div class="employment-section-heading employment-section-heading-centered">
            <div>
                <span class="employment-eyebrow">تقديم واضح من البداية للنهاية</span>
                <h2 id="process-heading">أربع خطوات فقط</h2>
            </div>
        </div>
        <ol class="employment-process-list">
            <li><span>01</span><div><h3>بياناتك الشخصية</h3><p>أدخل بيانات الهوية والتواصل وتفضيلات العمل.</p></div></li>
            <li><span>02</span><div><h3>المؤهلات والخبرة</h3><p>شارك مؤهلك وخبراتك والمهارات التي تميزك.</p></div></li>
            <li><span>03</span><div><h3>المرفقات</h3><p>أرفق سيرتك الذاتية ومستنداتك بصيغ آمنة.</p></div></li>
            <li><span>04</span><div><h3>المراجعة والإرسال</h3><p>راجع الطلب ووافق على الإقرار ثم أرسله.</p></div></li>
        </ol>
    </div>
</section>

<?php require BASE_PATH . '/includes/public_employment_footer.php'; ?>
