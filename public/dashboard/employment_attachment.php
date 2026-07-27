<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'hr_manager']);

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$attachmentIdRaw = (string)($_GET['id'] ?? '');
if ($attachmentIdRaw === '' || !ctype_digit($attachmentIdRaw) || (int)$attachmentIdRaw < 1) {
    http_response_code(400);
    exit('معرّف المرفق غير صالح.');
}

$stmt = db()->prepare(
    'SELECT att.id, att.original_name, att.stored_path, att.mime_type, att.file_size
     FROM employment_application_attachments att
     JOIN employment_applications app ON app.id = att.application_id
     WHERE att.id = ? LIMIT 1'
);
$stmt->execute([(int)$attachmentIdRaw]);
$attachment = $stmt->fetch();
if (!$attachment) {
    http_response_code(404);
    exit('المرفق المطلوب غير موجود.');
}

$storageRoot = realpath(BASE_PATH . '/storage/employment_uploads');
$relativePath = str_replace('\\', '/', (string)$attachment['stored_path']);
if ($storageRoot === false
    || $relativePath === ''
    || str_starts_with($relativePath, '/')
    || preg_match('/\A[A-Za-z]:\//', $relativePath)
    || str_contains($relativePath, "\0")) {
    error_log('Rejected employment attachment path for attachment #' . (int)$attachment['id']);
    http_response_code(404);
    exit('تعذر الوصول إلى المرفق.');
}

$filePath = realpath(BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
$rootPrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$pathPrefixComparison = $filePath === false
    ? 1
    : (DIRECTORY_SEPARATOR === '\\'
        ? strncasecmp($filePath, $rootPrefix, strlen($rootPrefix))
        : strncmp($filePath, $rootPrefix, strlen($rootPrefix)));
if ($filePath === false
    || $pathPrefixComparison !== 0
    || !is_file($filePath)
    || !is_readable($filePath)) {
    error_log('Missing or unsafe employment attachment #' . (int)$attachment['id']);
    http_response_code(404);
    exit('تعذر الوصول إلى المرفق.');
}

$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];
$mimeType = in_array($attachment['mime_type'], $allowedMimeTypes, true)
    ? $attachment['mime_type']
    : 'application/octet-stream';

$originalName = basename(str_replace('\\', '/', (string)$attachment['original_name']));
$originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName) ?: 'attachment';
$asciiFallback = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName) ?: 'attachment';
$asciiFallback = substr($asciiFallback, 0, 120);

session_write_close();
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addcslashes($asciiFallback, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($originalName));
header('Content-Length: ' . (string)filesize($filePath));
header('Content-Security-Policy: sandbox');
header('X-Download-Options: noopen');

$stream = fopen($filePath, 'rb');
if ($stream === false) {
    http_response_code(404);
    exit;
}
fpassthru($stream);
fclose($stream);
exit;
