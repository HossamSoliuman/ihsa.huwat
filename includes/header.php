<?php
/**
 * Shared authenticated dashboard shell.
 * Pages define $pageTitle and $activeRoute before including this file.
 */
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1d2835">
<title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
<script>
    (function () {
        var saved = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', saved === 'light' ? 'light' : 'dark');
    })();
</script>
<link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
<?php foreach (($pageStyles ?? []) as $pageStyle): ?>
<link rel="stylesheet" href="<?= e(assetUrl((string)$pageStyle)) ?>">
<?php endforeach; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<div class="layout">
    <header class="topbar">
        <div class="topbar-brand">
            <a href="<?= BASE_URL ?>/" class="brand-link" aria-label="<?= e(APP_NAME) ?>">
                <span class="brand-mark" aria-hidden="true"></span>
                <span class="brand-text">نظام إحصاء المصيد</span>
            </a>
        </div>

        <button class="sidebar-toggle icon-button" id="sidebarToggle" type="button" aria-label="فتح القائمة" aria-expanded="false">
            <span class="hamburger" aria-hidden="true"><i></i><i></i><i></i></span>
        </button>

        <div class="topbar-spacer"></div>

        <button class="icon-button topbar-action search-action" id="headerSearchToggle" type="button" aria-label="البحث">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
        </button>
        <a class="icon-button topbar-action notification-action" href="<?= BASE_URL ?>/dashboard/alerts.php" aria-label="التنبيهات">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
            <span class="notification-dot"></span>
        </a>
        <button class="theme-toggle icon-button topbar-action" id="themeToggle" type="button" aria-label="تبديل الوضع الداكن والفاتح">
            <svg class="theme-icon-dark" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"></path></svg>
            <svg class="theme-icon-light" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path></svg>
        </button>

        <div class="topbar-user">
            <span class="user-avatar" aria-hidden="true"><?= e(mb_substr($currentUserData['full_name'], 0, 1)) ?></span>
            <span class="user-meta">
                <span class="user-name"><?= e($currentUserData['full_name']) ?></span>
                <span class="user-role"><?= e($currentUserData['role_name']) ?></span>
            </span>
            <svg class="user-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
        </div>

        <form class="header-search" id="headerSearch" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <input id="headerSearchInput" type="search" placeholder="ابحث في القائمة..." autocomplete="off" aria-label="ابحث في القائمة">
            <button class="icon-button" id="headerSearchClose" type="button" aria-label="إغلاق البحث">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </form>
    </header>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <?php $lastGroup = null; foreach (sidebarMenu() as $item): ?>
                <?php if (!empty($item['hidden'])) continue; ?>
                <?php if (in_array($currentUserData['role_code'], $item['roles'], true)): ?>
                    <?php if (($item['group'] ?? null) !== $lastGroup): $lastGroup = $item['group'] ?? null; ?>
                        <div class="sidebar-section"><?= e($lastGroup) ?></div>
                    <?php endif; ?>
                    <a href="<?= BASE_URL . '/dashboard/' . $item['route'] ?>"
                       class="nav-link <?= ($activeRoute ?? '') === $item['route'] ? 'active' : '' ?>">
                        <span class="nav-icon" data-icon="<?= e($item['icon']) ?>" aria-hidden="true"></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link logout-link">
                <span class="nav-icon" data-icon="log-out" aria-hidden="true"></span>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="إغلاق القائمة"></button>

    <div class="main">
        <main class="content">
            <?php if (empty($hidePageHeading)): ?>
            <div class="content-heading">
                <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
            </div>
            <?php endif; ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
