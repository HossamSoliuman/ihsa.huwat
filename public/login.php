<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/' . currentUser()['dashboard']);
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'الرجاء إدخال اسم المستخدم وكلمة المرور.';
        } else {
            [$ok, $message] = attemptLogin($username, $password);
            if ($ok) {
                header('Location: ' . BASE_URL . '/dashboard/' . currentUser()['dashboard']);
                exit;
            }
            $error = $message;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول | <?= e(APP_NAME) ?></title>
<script>
    (function () {
        var saved = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', saved === 'dark' ? 'dark' : 'light');
    })();
</script>
<link rel="stylesheet" href="<?= e(assetUrl('css/app.css')) ?>">
</head>
<body class="login-page">
    <div class="login-card">
        <div style="text-align:center;"><span class="brand-icon">⚓</span></div>
        <h2 class="login-title"><?= e(APP_NAME) ?></h2>
        <p class="login-sub">الرجاء تسجيل الدخول للمتابعة</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" required autofocus
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">دخول</button>
        </form>
        <p class="login-public-link">تبحث عن فرصة عمل؟ <a href="<?= e(BASE_URL . '/#available-jobs') ?>">تصفح الوظائف المتاحة</a></p>
    </div>
</body>
</html>
