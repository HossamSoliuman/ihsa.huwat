<?php
/**
 * نظام المصادقة والصلاحيات
 * كل الدوال المتعلقة بتسجيل الدخول، الخروج، والتحقق من الدور
 */

const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

/**
 * محاولة تسجيل الدخول
 */
function attemptLogin(string $username, string $password): array
{
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // تحقق من عدد المحاولات الفاشلة الأخيرة
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS c FROM login_attempts
         WHERE username = ? AND success = 0
           AND created_at > (NOW() - INTERVAL ? MINUTE)"
    );
    $stmt->execute([$username, LOGIN_LOCKOUT_MINUTES]);
    if ((int)$stmt->fetch()['c'] >= MAX_LOGIN_ATTEMPTS) {
        return [false, 'تم إيقاف الحساب مؤقتًا بسبب محاولات دخول متكررة فاشلة. حاول بعد ' . LOGIN_LOCKOUT_MINUTES . ' دقيقة.'];
    }

    $stmt = $pdo->prepare(
        "SELECT u.*, r.code AS role_code, r.name_ar AS role_name, r.dashboard_route
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $success = false;

    if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        $success = true;

        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['role_code']  = $user['role_code'];
        $_SESSION['role_name']  = $user['role_name'];
        $_SESSION['dashboard']  = $user['dashboard_route'];
        $_SESSION['region_id']       = $user['region_id'];
        $_SESSION['governorate_id']  = $user['governorate_id'];
        $_SESSION['port_id']         = $user['port_id'];

        $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")
            ->execute([$user['id']]);
    }

    $pdo->prepare(
        "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)"
    )->execute([$username, $ip, $success ? 1 : 0]);

    if (!$success) {
        return [false, 'اسم المستخدم أو كلمة المرور غير صحيحة، أو أن الحساب غير مفعّل.'];
    }

    return [true, null];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'            => $_SESSION['user_id'],
        'full_name'     => $_SESSION['full_name'],
        'role_code'     => $_SESSION['role_code'],
        'role_name'     => $_SESSION['role_name'],
        'dashboard'     => $_SESSION['dashboard'],
        'region_id'      => $_SESSION['region_id'],
        'governorate_id' => $_SESSION['governorate_id'],
        'port_id'        => $_SESSION['port_id'],
    ];
}

/**
 * يجب استدعاؤها في أعلى كل صفحة محمية
 * $allowedRoles: مصفوفة بأكواد الأدوار المسموح لها بالدخول، أو [] للسماح لأي مستخدم مسجّل دخول
 */
function requireLogin(array $allowedRoles = []): array
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    $user = currentUser();

    if (!empty($allowedRoles) && !in_array($user['role_code'], $allowedRoles, true)) {
        header('Location: ' . BASE_URL . '/dashboard/' . $user['dashboard']);
        exit;
    }

    return $user;
}

/**
 * قائمة عناصر الشريط الجانبي مع تحديد أي الأدوار تراها
 * يُستخدم لبناء القائمة الجانبية ديناميكيًا حسب صلاحية المستخدم
 */
function sidebarMenu(): array
{
    return [
        ['route' => 'admin.php',          'label' => 'الرئيسية - الإدارة العليا',   'icon' => 'grid',      'group' => 'الرئيسية', 'roles' => ['super_admin']],
        ['route' => 'master_data.php',    'label' => 'البيانات الأساسية',           'icon' => 'database',  'group' => 'الرئيسية', 'roles' => ['super_admin']],

        ['route' => 'region.php',         'label' => 'لوحة المنطقة',                'icon' => 'map',       'group' => 'الإدارة الجغرافية', 'roles' => ['super_admin','region_manager']],
        ['route' => 'governorate.php',    'label' => 'لوحة المحافظة',               'icon' => 'flag',      'group' => 'الإدارة الجغرافية', 'roles' => ['super_admin','region_manager','gov_supervisor']],
        ['route' => 'port.php',           'label' => 'لوحة الميناء',                 'icon' => 'anchor',    'group' => 'الإدارة الجغرافية', 'roles' => ['super_admin','gov_supervisor','port_supervisor']],
        ['route' => 'coverage.php',       'label' => 'التغطية الجغرافية',            'icon' => 'globe',     'group' => 'الإدارة الجغرافية', 'roles' => ['super_admin','region_manager']],

        ['route' => 'trips.php',          'label' => 'القوارب والرحلات',            'icon' => 'ship',      'group' => 'العمليات', 'roles' => ['super_admin','gov_supervisor','port_supervisor','stat_employee']],
        ['route' => 'employee.php',       'label' => 'داشبورد موظف الإحصاء',        'icon' => 'user',      'group' => 'العمليات', 'roles' => ['super_admin','stat_employee']],
        ['route' => 'discrepancies.php',  'label' => 'الفروقات وجودة البيانات',     'icon' => 'alert-triangle', 'group' => 'العمليات', 'roles' => ['super_admin','port_supervisor','quality_supervisor']],

        ['route' => 'hr.php',             'label' => 'إدارة موظفي الإحصاء / الموارد البشرية', 'icon' => 'users', 'group' => 'الموظفون', 'roles' => ['super_admin','hr_manager']],
        ['route' => 'employee_performance.php', 'label' => 'أداء موظفي الإحصاء',    'icon' => 'bar-chart', 'group' => 'الموظفون', 'roles' => ['super_admin','hr_manager','gov_supervisor']],
        ['route' => 'attendance.php',     'label' => 'الحضور والمناوبات',           'icon' => 'clock',     'group' => 'الموظفون', 'roles' => ['super_admin','hr_manager','port_supervisor']],
        ['route' => 'payroll.php',        'label' => 'الرواتب والمستحقات',           'icon' => 'dollar-sign', 'group' => 'الموظفون', 'roles' => ['super_admin','finance_officer','hr_manager']],

        ['route' => 'reports.php',        'label' => 'التقارير والتحليلات',          'icon' => 'file-text', 'group' => 'التقارير والرقابة', 'roles' => ['super_admin','region_manager','gov_supervisor']],
        ['route' => 'alerts.php',         'label' => 'التنبيهات والرقابة',           'icon' => 'bell',      'group' => 'التقارير والرقابة', 'roles' => ['super_admin','port_supervisor','gov_supervisor']],
    ];
}
