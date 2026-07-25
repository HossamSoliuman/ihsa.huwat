<?php
/**
 * سكربت تهيئة أولي - يُستخدم مرة واحدة فقط بعد استيراد schema.sql
 * لإنشاء حساب "الإدارة العليا" الأول بكلمة مرور حقيقية ومشفّرة.
 * بعد نجاح الإنشاء يحذف نفسه تلقائيًا من السيرفر لأسباب أمنية.
 */
require_once __DIR__ . '/../config/database.php';

$pdo = db();

// إن وُجد أي مستخدم super_admin بالفعل، يُمنع تشغيل السكربت مجددًا
$exists = $pdo->query(
    "SELECT COUNT(*) c FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE r.code = 'super_admin'"
)->fetch()['c'];

$done = false;
$error = null;

if ($exists > 0) {
    $error = 'تم إنشاء حساب الإدارة العليا مسبقًا. لأسباب أمنية يجب حذف ملف setup.php يدويًا الآن.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    if ($fullName === '' || $username === '' || $password === '') {
        $error = 'جميع الحقول مطلوبة.';
    } elseif (strlen($password) < 8) {
        $error = 'يجب ألا تقل كلمة المرور عن 8 أحرف.';
    } elseif ($password !== $confirm) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } else {
        $roleId = $pdo->query("SELECT id FROM roles WHERE code = 'super_admin'")->fetchColumn();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (role_id, full_name, username, password_hash, is_active)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$roleId, $fullName, $username, $hash]);

        $done = true;
        // محاولة حذف السكربت تلقائيًا (قد لا تملك صلاحية الحذف على بعض الاستضافات)
        @unlink(__FILE__);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تهيئة النظام</title>
<script>
    (function () {
        var saved = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', saved === 'dark' ? 'dark' : 'light');
    })();
</script>
<link rel="stylesheet" href="../public/assets/css/app.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h2 class="login-title">تهيئة النظام لأول مرة</h2>
        <p class="login-sub">إنشاء حساب الإدارة العليا</p>

        <?php if ($done): ?>
            <div class="alert alert-success">
                تم إنشاء الحساب بنجاح. تم حذف ملف setup.php تلقائيًا.
                <br><a href="login.php">اذهب لتسجيل الدخول</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($exists == 0): ?>
            <form method="post" action="">
                <div class="form-group">
                    <label>الاسم الكامل</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirm" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary btn-block">إنشاء الحساب</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
