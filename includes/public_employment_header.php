<?php
declare(strict_types=1);

/**
 * Public employment shell. The including page supplies $pageTitle,
 * $pageDescription, $activePublicRoute and optionally $bodyClass.
 */
$publicTitle = $pageTitle ?? 'بوابة التوظيف';
$publicDescription = $pageDescription ?? 'اكتشف فرص العمل المتاحة وقدّم طلبك إلكترونياً.';
$publicRoute = $activePublicRoute ?? '';

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
?>
<!doctype html>
<html lang="ar" dir="rtl" data-employment-page>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2942">
    <meta name="description" content="<?= e($publicDescription) ?>">
    <title><?= e($publicTitle) ?> | بوابة التوظيف</title>
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            var forceDark = <?= !empty($forcePublicDarkTheme) ? 'true' : 'false' ?>;
            document.documentElement.setAttribute('data-theme', forceDark || saved === 'dark' ? 'dark' : 'light');
        }());
    </script>
    <link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('css/employment.css')) ?>">
</head>
<body class="employment-public <?= e($bodyClass ?? '') ?>">
<a class="employment-skip-link" href="#main-content">تجاوز إلى المحتوى الرئيسي</a>

<?php if (empty($hidePublicHeader)): ?>
<header class="employment-header" data-public-header>
    <div class="employment-container employment-header-inner">
        <a class="employment-brand" href="<?= e(BASE_URL . '/') ?>" aria-label="بوابة التوظيف - الصفحة الرئيسية">
            <span class="employment-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" role="img">
                    <path d="M24 4v29M17 12h14M12 22c0 8 5.4 14 12 14s12-6 12-14M8 24h8M32 24h8"></path>
                    <circle cx="24" cy="7" r="3"></circle>
                    <path d="M14 39c3-2 6-2 10 0s7 2 10 0"></path>
                </svg>
            </span>
            <span>
                <strong>بوابة التوظيف</strong>
                <small>نظام إحصاء المصيد وإدارة الموانئ</small>
            </span>
        </a>

        <button class="employment-nav-toggle" type="button" data-public-nav-toggle aria-controls="employment-public-nav" aria-expanded="false">
            <span class="sr-only">فتح قائمة التنقل</span>
            <i></i><i></i><i></i>
        </button>

        <nav class="employment-nav" id="employment-public-nav" aria-label="التنقل الرئيسي" data-public-nav>
            <a href="<?= e(BASE_URL . '/') ?>"<?= $publicRoute === 'home' ? ' aria-current="page"' : '' ?>>الرئيسية</a>
            <a href="<?= e(BASE_URL . '/#available-jobs') ?>">الوظائف المتاحة</a>
            <a href="<?= e(BASE_URL . '/#application-process') ?>">خطوات التقديم</a>
            <a href="<?= e(BASE_URL . '/login.php') ?>">دخول الموظفين</a>
            <button class="employment-theme-toggle" type="button" data-employment-theme-toggle aria-label="تبديل الوضع الداكن والفاتح">
                <svg class="employment-moon-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"></path></svg>
                <svg class="employment-sun-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
            </button>
        </nav>
    </div>
</header>
<?php endif; ?>

<main id="main-content" class="employment-main" tabindex="-1">
