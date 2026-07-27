<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/employment_public_functions.php';

$jobs = [];
$loadError = null;

try {
    $statement = db()->query(
        "SELECT j.*, p.name AS port_name
         FROM employment_jobs j
         LEFT JOIN ports p ON p.id = j.port_id
         WHERE j.status = 'open'
           AND (j.published_at IS NULL OR j.published_at <= NOW())
           AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())
         ORDER BY COALESCE(j.published_at, j.created_at) DESC, j.id DESC"
    );
    $jobs = $statement->fetchAll();
} catch (Throwable $exception) {
    error_log('Employment landing page error: ' . $exception->getMessage());
    $loadError = 'تعذر تحميل الوظائف الآن. يرجى المحاولة مرة أخرى لاحقاً.';
}

$pageTitle = 'الوظائف المتاحة';
$pageDescription = 'الوظائف المفتوحة والتقديم الإلكتروني.';
$activePublicRoute = 'home';
$bodyClass = 'employment-home-page employment-simple-home';
$hidePublicHeader = true;
$forcePublicDarkTheme = true;

require __DIR__ . '/../includes/public_employment_header.php';
?>

<section class="employment-simple-hero" aria-labelledby="careers-title">
    <div class="employment-container employment-simple-hero-grid">
        <div class="employment-simple-hero-copy">
            <p class="employment-eyebrow">IHSA / CAREERS</p>
            <h1 id="careers-title">الوظائف المتاحة</h1>
            <p>اختر الوظيفة المناسبة وقدّم طلبك إلكترونياً.</p>
            <div class="employment-simple-hero-actions">
                <a class="employment-button employment-button-primary" href="#available-jobs">عرض الوظائف</a>
                <span><i aria-hidden="true"></i><?= numberAr(count($jobs)) ?> وظيفة مفتوحة</span>
            </div>
        </div>
        <aside class="employment-hero-telemetry" aria-label="ملخص الوظائف المتاحة">
            <span class="employment-telemetry-label">OPEN POSITIONS</span>
            <strong><?= numberAr(count($jobs)) ?></strong>
            <p>فرصة وظيفية متاحة للتقديم حالياً</p>
            <div class="employment-signal-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        </aside>
    </div>
</section>

<section class="employment-container employment-simple-jobs" id="available-jobs" aria-labelledby="available-jobs-title">
    <header class="employment-simple-section-heading">
        <div>
            <p class="employment-eyebrow">الفرص الحالية</p>
            <h2 id="available-jobs-title">اختر الوظيفة</h2>
        </div>
        <span><?= numberAr(count($jobs)) ?> نتيجة</span>
    </header>

    <?php if ($loadError): ?>
        <div class="employment-empty-state" role="alert">
            <h3>تعذر عرض الوظائف</h3>
            <p><?= e($loadError) ?></p>
        </div>
    <?php elseif (!$jobs): ?>
        <div class="employment-empty-state">
            <h3>لا توجد وظائف مفتوحة حالياً</h3>
            <p>ستظهر الوظائف الجديدة هنا عند نشرها.</p>
        </div>
    <?php else: ?>
        <div class="employment-simple-job-grid">
            <?php foreach ($jobs as $job): ?>
                <?php
                $jobId = (int)$job['id'];
                $location = $job['port_name'] ?: ($job['city'] ?: 'مواقع متعددة');
                ?>
                <article class="employment-simple-job-card" aria-labelledby="job-title-<?= $jobId ?>">
                    <div class="employment-simple-job-topline">
                        <span><?= e(employmentPublicTypeLabel($job['employment_type'])) ?></span>
                        <time datetime="<?= e((string)($job['application_deadline'] ?? '')) ?>">
                            <?= $job['application_deadline'] ? 'حتى ' . e(employmentPublicDate($job['application_deadline'])) : 'التقديم مفتوح' ?>
                        </time>
                    </div>
                    <h3 id="job-title-<?= $jobId ?>"><?= e($job['title_ar']) ?></h3>
                    <p class="employment-simple-job-meta">
                        <?= e($job['department'] ?: 'غير محدد') ?>
                        <span aria-hidden="true">•</span>
                        <?= e($location) ?>
                    </p>
                    <p class="employment-simple-job-summary"><?= e(employmentPublicExcerpt($job['summary'], 120)) ?></p>
                    <div class="employment-simple-job-actions">
                        <a class="employment-button employment-button-primary" href="<?= e(BASE_URL . '/apply.php?job_id=' . $jobId) ?>">قدّم الآن</a>
                        <a class="employment-text-link" href="<?= e(BASE_URL . '/job.php?id=' . $jobId) ?>">التفاصيل</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="employment-simple-process" id="application-process" aria-labelledby="application-process-title">
    <div class="employment-container">
        <header class="employment-simple-section-heading">
            <div>
                <p class="employment-eyebrow">التقديم</p>
                <h2 id="application-process-title">أربع خطوات فقط</h2>
            </div>
        </header>
        <ol>
            <li><span>01</span><strong>اختر الوظيفة</strong></li>
            <li><span>02</span><strong>أدخل بياناتك</strong></li>
            <li><span>03</span><strong>ارفع المرفقات</strong></li>
            <li><span>04</span><strong>راجع وأرسل</strong></li>
        </ol>
    </div>
</section>

<section class="employment-container employment-simple-login">
    <p>لديك حساب موظف؟</p>
    <a class="employment-button employment-button-outline" href="<?= e(BASE_URL . '/login.php') ?>">تسجيل الدخول</a>
</section>

<?php require __DIR__ . '/../includes/public_employment_footer.php'; ?>
