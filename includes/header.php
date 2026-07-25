<?php
/**
 * يُستدعى بعد تحديد $pageTitle و $activeRoute في كل صفحة داشبورد
 * ويفترض أن requireLogin() تم استدعاؤها مسبقًا وأن $currentUserData موجودة
 */
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
<script>
    // تطبيق الوضع (داكن افتراضيًا) قبل رسم الصفحة لمنع وميض التغيير
    (function () {
        var saved = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', saved === 'dark' ? 'dark' : 'light');
    })();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">⚓</span>
            <span class="brand-text">نظام إحصاء المصيد</span>
        </div>

        <nav class="sidebar-nav">
            <?php $lastGroup = null; foreach (sidebarMenu() as $item): ?>
                <?php if (!empty($item['hidden'])) continue; ?>
                <?php if (in_array($currentUserData['role_code'], $item['roles'], true)): ?>
                    <?php if (($item['group'] ?? null) !== $lastGroup): $lastGroup = $item['group'] ?? null; ?>
                        <div class="sidebar-section"><?= e($lastGroup) ?></div>
                    <?php endif; ?>
                    <a href="<?= BASE_URL . '/dashboard/' . $item['route'] ?>"
                       class="nav-link <?= ($activeRoute ?? '') === $item['route'] ? 'active' : '' ?>">
                        <span class="nav-icon" data-icon="<?= e($item['icon']) ?>"></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link logout-link">تسجيل الخروج</a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة">☰</button>
            <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="تبديل الوضع الداكن/الفاتح">
                <span class="theme-icon-dark">🌙</span>
                <span class="theme-icon-light">☀️</span>
            </button>
            <div class="topbar-user">
                <span class="user-role"><?= e($currentUserData['role_name']) ?></span>
                <span class="user-name"><?= e($currentUserData['full_name']) ?></span>
            </div>
        </header>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
