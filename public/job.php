<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/employment_public_functions.php';

$rawJobId = $_GET['id'] ?? null;
$jobId = is_scalar($rawJobId) && filter_var($rawJobId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    ? (int)$rawJobId
    : 0;
$job = null;
$loadError = null;

if ($jobId > 0) {
    try {
        $statement = db()->prepare(
            "SELECT j.*, p.name AS port_name
             FROM employment_jobs j
             LEFT JOIN ports p ON p.id = j.port_id
             WHERE j.id = :id
               AND j.status = 'open'
               AND (j.published_at IS NULL OR j.published_at <= NOW())
               AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())
             LIMIT 1"
        );
        $statement->execute(['id' => $jobId]);
        $job = $statement->fetch() ?: null;
    } catch (Throwable $exception) {
        error_log('Public employment job detail error: ' . $exception->getMessage());
        $loadError = 'تعذر تحميل تفاصيل الوظيفة في الوقت الحالي.';
        http_response_code(500);
    }
}

if ($job === null && $loadError === null) {
    http_response_code(404);
}

$pageTitle = $job ? (string)$job['title_ar'] : 'الوظيفة غير متاحة';
$pageDescription = $job
    ? employmentPublicExcerpt((string)($job['summary'] ?: $job['description']), 155)
    : 'هذه الوظيفة غير متاحة للتقديم حالياً.';
$activePublicRoute = 'jobs';
$bodyClass = 'employment-job-detail-page employment-hud-public';
$hidePublicHeader = true;
$forcePublicDarkTheme = true;

require BASE_PATH . '/includes/public_employment_header.php';
?>

<?php if (!$job): ?>
    <section class="employment-state-page">
        <div class="employment-container employment-state-card">
            <span class="employment-state-icon" aria-hidden="true">
                <svg viewBox="0 0 72 72"><path d="M17 23h38v34H17zM26 23v-7h20v7M17 35h38M31 35v5h10v-5"></path><path d="m25 49 22-22"></path></svg>
            </span>
            <span class="employment-eyebrow"><?= $loadError ? 'خطأ مؤقت' : 'انتهى التقديم أو تغير الرابط' ?></span>
            <h1><?= $loadError ? 'تعذر عرض الوظيفة' : 'هذه الوظيفة غير متاحة' ?></h1>
            <p><?= e($loadError ?? 'قد تكون فترة التقديم انتهت أو تم إغلاق الشاغر. يمكنك استعراض الفرص المفتوحة حالياً.') ?></p>
            <a class="employment-button employment-button-primary" href="<?= e(BASE_URL . '/#available-jobs') ?>">العودة إلى الوظائف</a>
        </div>
    </section>
<?php else: ?>
    <?php
    $location = trim(implode('، ', array_filter([(string)($job['port_name'] ?? ''), (string)($job['city'] ?? '')])));
    $responsibilities = employmentPublicLines((string)($job['responsibilities'] ?? ''));
    $requirements = employmentPublicLines((string)($job['requirements'] ?? ''));
    ?>
    <section class="employment-job-banner">
        <div class="employment-container">
            <nav class="employment-breadcrumb" aria-label="مسار التنقل">
                <a href="<?= e(BASE_URL . '/#available-jobs') ?>">الوظائف المتاحة</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?= e((string)$job['title_ar']) ?></span>
            </nav>

            <div class="employment-job-banner-grid">
                <div>
                    <div class="employment-job-badges">
                        <span><?= e(employmentPublicTypeLabel((string)$job['employment_type'])) ?></span>
                        <?php if (!empty($job['department'])): ?><span><?= e((string)$job['department']) ?></span><?php endif; ?>
                    </div>
                    <h1><?= e((string)$job['title_ar']) ?></h1>
                    <?php if (!empty($job['summary'])): ?><p><?= e((string)$job['summary']) ?></p><?php endif; ?>
                </div>
                <div class="employment-job-reference-block">
                    <small>الرقم المرجعي</small>
                    <strong class="mono"><?= e((string)$job['reference_no']) ?></strong>
                </div>
            </div>
        </div>
    </section>

    <section class="employment-section employment-job-content-section">
        <div class="employment-container employment-job-layout">
            <article class="employment-job-content">
                <?php if (!empty($job['description'])): ?>
                    <section aria-labelledby="job-description-heading">
                        <span class="employment-content-number">01</span>
                        <div>
                            <h2 id="job-description-heading">عن الوظيفة</h2>
                            <div class="employment-rich-text"><?= nl2br(e((string)$job['description'])) ?></div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($responsibilities): ?>
                    <section aria-labelledby="job-responsibilities-heading">
                        <span class="employment-content-number">02</span>
                        <div>
                            <h2 id="job-responsibilities-heading">المهام والمسؤوليات</h2>
                            <ul class="employment-check-list">
                                <?php foreach ($responsibilities as $responsibility): ?>
                                    <li><?= e($responsibility) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($requirements): ?>
                    <section aria-labelledby="job-requirements-heading">
                        <span class="employment-content-number">03</span>
                        <div>
                            <h2 id="job-requirements-heading">المؤهلات والمتطلبات</h2>
                            <ul class="employment-check-list">
                                <?php foreach ($requirements as $requirement): ?>
                                    <li><?= e($requirement) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                <?php endif; ?>
            </article>

            <aside class="employment-job-sidebar" aria-label="ملخص الوظيفة">
                <div class="employment-job-summary-card">
                    <h2>ملخص الوظيفة</h2>
                    <dl>
                        <?php if ($location !== ''): ?>
                            <div><dt>الموقع</dt><dd><?= e($location) ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($job['department'])): ?>
                            <div><dt>الإدارة</dt><dd><?= e((string)$job['department']) ?></dd></div>
                        <?php endif; ?>
                        <div><dt>نوع الدوام</dt><dd><?= e(employmentPublicTypeLabel((string)$job['employment_type'])) ?></dd></div>
                        <div><dt>عدد الشواغر</dt><dd><?= (int)$job['vacancies'] ?></dd></div>
                        <div><dt>الراتب</dt><dd><?= e(employmentPublicSalary($job['salary_min'], $job['salary_max'])) ?></dd></div>
                        <div><dt>آخر موعد للتقديم</dt><dd><?= e(employmentPublicDate($job['application_deadline'] ? (string)$job['application_deadline'] : null)) ?></dd></div>
                    </dl>
                    <a class="employment-button employment-button-primary employment-button-block" href="<?= e(BASE_URL . '/apply.php?job_id=' . (int)$job['id']) ?>">التقديم على الوظيفة</a>
                    <p class="employment-privacy-note">لن تُستخدم بياناتك إلا لغرض دراسة طلب التوظيف.</p>
                </div>
                <a class="employment-back-link" href="<?= e(BASE_URL . '/#available-jobs') ?>"><span aria-hidden="true">→</span> العودة إلى جميع الوظائف</a>
            </aside>
        </div>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/includes/public_employment_footer.php'; ?>
