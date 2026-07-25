<?php
/**
 * إعدادات الاتصال بقاعدة البيانات
 * عدّل القيم التالية حسب بيئة الاستضافة الخاصة بك
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'fisheries_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // لا نعرض تفاصيل الاتصال الحقيقية في بيئة الإنتاج
            error_log('DB Connection Error: ' . $e->getMessage());
            die('تعذر الاتصال بقاعدة البيانات. حاول لاحقًا أو راجع الدعم الفني.');
        }
    }

    return $pdo;
}
