<?php
/**
 * إعدادات عامة للمشروع
 */

// إظهار الأخطاء أثناء التطوير فقط - عطّلها في بيئة الإنتاج
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('APP_NAME', 'نظام إحصاء المصيد وإدارة الموانئ');
define('BASE_PATH', dirname(__DIR__));

// المسار الأساسي للرابط (عدّله إذا كان المشروع داخل مجلد فرعي على السيرفر)
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$publicRoot = realpath(BASE_PATH . '/public');
$baseUrl = '';

// Derive the URL from the web server paths, allowing any project folder name
// and also supporting a virtual host whose document root is public/ itself.
if ($documentRoot && $publicRoot
    && strncasecmp($publicRoot, $documentRoot, strlen($documentRoot)) === 0) {
    $baseUrl = str_replace('\\', '/', substr($publicRoot, strlen($documentRoot)));
} else {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $publicPosition = strpos($scriptName, '/public/');
    if ($publicPosition !== false) {
        $baseUrl = substr($scriptName, 0, $publicPosition + strlen('/public'));
    }
}

define('BASE_URL', rtrim($baseUrl, '/'));

date_default_timezone_set('Asia/Riyadh');

session_name('fisheries_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth.php';
