<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'region_manager', 'gov_supervisor', 'port_supervisor']);
$pageTitle = 'إدارة المرافئ';
$activeRoute = 'harbor_details.php';
$hidePageHeading = true;
$bodyClass = 'harbor-details-page';
$pdo = db();
$role = $currentUserData['role_code'];

function harborRedirect(int $portId = 0, string $tab = 'overview'): string
{
    $query = $portId > 0 ? '?port_id=' . $portId : '';
    if ($portId > 0 && $tab !== 'overview') $query .= '&tab=' . rawurlencode($tab);
    return BASE_URL . '/dashboard/harbor_details.php' . $query;
}

function harborPercent(int $occupied, int $capacity): float
{
    return $capacity > 0 ? min(100, round(($occupied / $capacity) * 100, 1)) : 0;
}

function harborNullable(string $key): ?string
{
    $value = trim((string)($_POST[$key] ?? ''));
    return $value === '' ? null : $value;
}

function harborEnum(string $key, array $allowed, string $fallback): string
{
    $value = (string)($_POST[$key] ?? '');
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function protectedIdentity(string $identity): ?string
{
    $identity = trim($identity);
    return $identity === '' ? null : password_hash($identity, PASSWORD_DEFAULT);
}

function harborRecord(PDO $pdo, string $table, int $recordId, int $portId): array
{
    $allowed = ['boats', 'harbor_workers', 'harbor_licenses', 'harbor_violations'];
    if (!in_array($table, $allowed, true) || $recordId < 1) {
        throw new InvalidArgumentException('السجل المحدد غير صالح.');
    }
    $portColumn = $table === 'boats' ? 'home_port_id' : 'port_id';
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? AND {$portColumn} = ?");
    $stmt->execute([$recordId, $portId]);
    $record = $stmt->fetch();
    if (!$record) throw new InvalidArgumentException('السجل غير موجود في هذا المرفأ.');
    return $record;
}

/** @return array{path:string,full_path:string}|null */
function storeHarborAttachment(string $field, string $category): ?array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new InvalidArgumentException('تعذر رفع المرفق. أعد اختيار الملف وحاول مرة أخرى.');
    }
    if ((int)($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new InvalidArgumentException('حجم المرفق يجب ألا يتجاوز 10 ميجابايت.');
    }
    $mimeTypes = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($mimeTypes[$mime])) throw new InvalidArgumentException('المرفق غير مدعوم. استخدم PDF أو JPG أو PNG أو WEBP.');

    $directory = BASE_PATH . '/storage/harbor_uploads/' . $category;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('تعذر إنشاء مجلد مرفقات المرفأ.');
    }
    $filename = bin2hex(random_bytes(20)) . '.' . $mimeTypes[$mime];
    $fullPath = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) throw new RuntimeException('تعذر حفظ المرفق المرفوع.');
    return ['path' => $category . '/' . $filename, 'full_path' => $fullPath];
}

function harborAttachmentFullPath(string $relativePath): ?string
{
    $root = realpath(BASE_PATH . '/storage/harbor_uploads');
    $file = realpath(BASE_PATH . '/storage/harbor_uploads/' . ltrim(str_replace('\\', '/', $relativePath), '/'));
    if (!$root || !$file || !is_file($file)) return null;
    $rootPrefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
    return str_starts_with(str_replace('\\', '/', $file), $rootPrefix) ? $file : null;
}

function removeHarborAttachment(?string $relativePath): void
{
    if (!$relativePath) return;
    $file = harborAttachmentFullPath($relativePath);
    if ($file) @unlink($file);
}

$governoratesSql = "SELECT g.id, g.name, g.region_id, r.name AS region_name FROM governorates g JOIN regions r ON r.id = g.region_id WHERE 1=1";
$governoratesParams = [];
if ($role === 'region_manager') {
    $governoratesSql .= ' AND g.region_id = ?';
    $governoratesParams[] = (int)$currentUserData['region_id'];
} elseif ($role === 'gov_supervisor') {
    $governoratesSql .= ' AND g.id = ?';
    $governoratesParams[] = (int)$currentUserData['governorate_id'];
} elseif ($role === 'port_supervisor') {
    $governoratesSql .= ' AND g.id = (SELECT governorate_id FROM ports WHERE id = ?)';
    $governoratesParams[] = (int)$currentUserData['port_id'];
}
$governoratesSql .= ' ORDER BY r.name, g.name';
$stmt = $pdo->prepare($governoratesSql);
$stmt->execute($governoratesParams);
$governoratesList = $stmt->fetchAll();
$allowedGovernorateIds = array_map('intval', array_column($governoratesList, 'id'));

$portsSql = "SELECT p.*, g.name AS governorate_name, g.region_id, r.name AS region_name
             FROM ports p JOIN governorates g ON g.id = p.governorate_id JOIN regions r ON r.id = g.region_id WHERE 1=1";
$portsParams = [];
if ($role === 'region_manager') {
    $portsSql .= ' AND g.region_id = ?'; $portsParams[] = (int)$currentUserData['region_id'];
} elseif ($role === 'gov_supervisor') {
    $portsSql .= ' AND p.governorate_id = ?'; $portsParams[] = (int)$currentUserData['governorate_id'];
} elseif ($role === 'port_supervisor') {
    $portsSql .= ' AND p.id = ?'; $portsParams[] = (int)$currentUserData['port_id'];
}
$portsSql .= ' ORDER BY r.name, g.name, p.name';
$stmt = $pdo->prepare($portsSql);
$stmt->execute($portsParams);
$portsList = $stmt->fetchAll();
$allowedPortIds = array_map('intval', array_column($portsList, 'id'));
$canCreateHarbor = in_array($role, ['super_admin', 'region_manager', 'gov_supervisor'], true);
$canDeleteHarbor = $role === 'super_admin';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $isPost ? (string)($_POST['action'] ?? '') : '';
$requestedPortId = (int)($isPost ? ($_POST['port_id'] ?? 0) : ($_GET['port_id'] ?? 0));
if ($isPost && $action !== 'create_port' && !in_array($requestedPortId, $allowedPortIds, true)) {
    http_response_code(403);
    exit('غير مصرح لك بتعديل هذا المرفأ.');
}
$portId = in_array($requestedPortId, $allowedPortIds, true) ? $requestedPortId : (int)($allowedPortIds[0] ?? 0);
$redirectUrl = harborRedirect($portId, (string)($_POST['return_tab'] ?? 'overview'));

if ($portId && isset($_GET['attachment'], $_GET['attachment_id'])) {
    $type = (string)$_GET['attachment'];
    $attachmentId = (int)$_GET['attachment_id'];
    $sources = ['license' => ['table' => 'harbor_licenses', 'category' => 'licenses'], 'violation' => ['table' => 'harbor_violations', 'category' => 'violations']];
    if (!isset($sources[$type]) || $attachmentId < 1) { http_response_code(404); exit('المرفق غير موجود.'); }
    $source = $sources[$type];
    $stmt = $pdo->prepare("SELECT attachment_path FROM {$source['table']} WHERE id = ? AND port_id = ?");
    $stmt->execute([$attachmentId, $portId]);
    $path = (string)$stmt->fetchColumn();
    if ($path === '' || !str_starts_with($path, $source['category'] . '/')) { http_response_code(404); exit('المرفق غير موجود.'); }
    $file = harborAttachmentFullPath($path);
    if (!$file) { http_response_code(404); exit('المرفق غير موجود.'); }
    header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename="harbor-attachment-' . $attachmentId . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

if ($isPost) {
    if (!verifyCsrf()) redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    $pendingUpload = null;
    try {
        switch ($action) {
            case 'create_port':
                if (!$canCreateHarbor) throw new InvalidArgumentException('لا تملك صلاحية إنشاء مرفأ.');
                $name = trim((string)($_POST['name'] ?? ''));
                $governorateId = (int)($_POST['governorate_id'] ?? 0);
                if ($name === '' || !in_array($governorateId, $allowedGovernorateIds, true)) throw new InvalidArgumentException('اسم المرفأ والمحافظة الصحيحة مطلوبان.');
                $url = harborNullable('location_url');
                if ($url && !filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException('رابط الموقع غير صحيح.');
                $latitude = harborNullable('latitude'); $longitude = harborNullable('longitude');
                if ($latitude !== null && (!is_numeric($latitude) || (float)$latitude < -90 || (float)$latitude > 90)) throw new InvalidArgumentException('خط العرض يجب أن يكون بين -90 و90.');
                if ($longitude !== null && (!is_numeric($longitude) || (float)$longitude < -180 || (float)$longitude > 180)) throw new InvalidArgumentException('خط الطول يجب أن يكون بين -180 و180.');
                $stmt = $pdo->prepare('INSERT INTO ports (governorate_id, name, location_name, location_url, is_active, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$governorateId, $name, harborNullable('location_name'), $url, isset($_POST['is_active']) ? 1 : 0, $latitude, $longitude]);
                redirectWithMessage(harborRedirect((int)$pdo->lastInsertId()), 'success', 'تم إنشاء المرفأ وإتاحته لاستكمال بياناته.');

            case 'update_port':
                $name = trim((string)($_POST['name'] ?? ''));
                $governorateId = (int)($_POST['governorate_id'] ?? 0);
                if ($name === '' || !in_array($governorateId, $allowedGovernorateIds, true)) throw new InvalidArgumentException('اسم المرفأ والمحافظة الصحيحة مطلوبان.');
                $url = harborNullable('location_url');
                if ($url && !filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException('رابط الموقع غير صحيح.');
                $latitude = harborNullable('latitude'); $longitude = harborNullable('longitude');
                if ($latitude !== null && (!is_numeric($latitude) || (float)$latitude < -90 || (float)$latitude > 90)) throw new InvalidArgumentException('خط العرض يجب أن يكون بين -90 و90.');
                if ($longitude !== null && (!is_numeric($longitude) || (float)$longitude < -180 || (float)$longitude > 180)) throw new InvalidArgumentException('خط الطول يجب أن يكون بين -180 و180.');
                $pdo->prepare('UPDATE ports SET governorate_id = ?, name = ?, location_name = ?, location_url = ?, is_active = ?, latitude = ?, longitude = ? WHERE id = ?')
                    ->execute([$governorateId, $name, harborNullable('location_name'), $url, isset($_POST['is_active']) ? 1 : 0, $latitude, $longitude, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تم تحديث جميع بيانات المرفأ.');

            case 'delete_port':
                if (!$canDeleteHarbor) throw new InvalidArgumentException('حذف المرفأ متاح للإدارة العليا فقط.');
                $confirmedName = trim((string)($_POST['confirm_name'] ?? ''));
                $port = array_values(array_filter($portsList, fn($item) => (int)$item['id'] === $portId))[0] ?? null;
                if (!$port || $confirmedName !== $port['name']) throw new InvalidArgumentException('اكتب اسم المرفأ مطابقًا لتأكيد الحذف.');
                $attachmentStmt = $pdo->prepare(
                    "SELECT attachment_path FROM harbor_licenses WHERE port_id = ? AND attachment_path IS NOT NULL
                     UNION ALL
                     SELECT attachment_path FROM harbor_violations WHERE port_id = ? AND attachment_path IS NOT NULL"
                );
                $attachmentStmt->execute([$portId, $portId]);
                $portAttachments = array_column($attachmentStmt->fetchAll(), 'attachment_path');
                $pdo->prepare('DELETE FROM ports WHERE id = ?')->execute([$portId]);
                foreach ($portAttachments as $attachmentPath) removeHarborAttachment($attachmentPath);
                $nextPort = (int)(array_values(array_filter($allowedPortIds, fn($id) => $id !== $portId))[0] ?? 0);
                redirectWithMessage(harborRedirect($nextPort), 'success', 'تم حذف المرفأ وسجلاته التابعة القابلة للحذف.');

            case 'update_capacities':
                foreach (['large', 'small', 'recreational'] as $type) {
                    $capacity = filter_var($_POST['capacity_' . $type] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                    if ($capacity === false) throw new InvalidArgumentException('سعة القوارب يجب أن تكون رقمًا صحيحًا موجبًا أو صفرًا.');
                    $status = ($_POST['status_' . $type] ?? 'available') === 'stopped' ? 'stopped' : 'available';
                    $pdo->prepare("INSERT INTO harbor_boat_capacities (port_id, boat_type, capacity, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), status = VALUES(status)")
                        ->execute([$portId, $type, $capacity, $status]);
                }
                redirectWithMessage($redirectUrl, 'success', 'تم تحديث الطاقة الاستيعابية وحالة الأرصفة.');

            case 'add_boat':
            case 'update_boat':
                $boatId = (int)($_POST['record_id'] ?? 0);
                if ($action === 'update_boat') harborRecord($pdo, 'boats', $boatId, $portId);
                $name = trim((string)($_POST['boat_name'] ?? ''));
                if ($name === '') throw new InvalidArgumentException('اسم القارب مطلوب.');
                $values = [$name, harborNullable('registration_no'), harborEnum('boat_type', ['large','small','recreational','unclassified'], 'unclassified'), harborEnum('harbor_status', ['occupied','disabled','inactive','unclassified'], 'unclassified')];
                if ($action === 'add_boat') {
                    $pdo->prepare('INSERT INTO boats (name, registration_no, boat_type, harbor_status, home_port_id) VALUES (?, ?, ?, ?, ?)')->execute([...$values, $portId]);
                    $message = 'تمت إضافة القارب.';
                } else {
                    $pdo->prepare('UPDATE boats SET name = ?, registration_no = ?, boat_type = ?, harbor_status = ? WHERE id = ? AND home_port_id = ?')->execute([...$values, $boatId, $portId]);
                    $message = 'تم تحديث بيانات القارب.';
                }
                redirectWithMessage(harborRedirect($portId, 'boats'), 'success', $message);

            case 'delete_boat':
                $boatId = (int)($_POST['record_id'] ?? 0); harborRecord($pdo, 'boats', $boatId, $portId);
                $pdo->prepare('DELETE FROM boats WHERE id = ? AND home_port_id = ?')->execute([$boatId, $portId]);
                redirectWithMessage(harborRedirect($portId, 'boats'), 'success', 'تم حذف القارب.');

            case 'add_worker':
            case 'update_worker':
                $workerId = (int)($_POST['record_id'] ?? 0);
                $existingWorker = $action === 'update_worker' ? harborRecord($pdo, 'harbor_workers', $workerId, $portId) : null;
                $name = trim((string)($_POST['employee_name'] ?? ''));
                if ($name === '') throw new InvalidArgumentException('اسم العامل مطلوب.');
                $identity = protectedIdentity((string)($_POST['identity_number'] ?? ''));
                if (!$identity && $existingWorker) $identity = $existingWorker['identity_number'];
                $start = harborNullable('start_date'); $end = harborNullable('end_date');
                if ($start && $end && $end < $start) throw new InvalidArgumentException('تاريخ الانتهاء يجب ألا يسبق تاريخ البداية.');
                $values = [$name, $identity, harborEnum('nationality', ['saudi','non_saudi'], 'saudi'), harborEnum('worker_type', ['supervisor','contractor','fisherman','foreign_worker'], 'fisherman'), harborNullable('mobile_number'), harborEnum('employment_status', ['active','suspended','expired'], 'active'), $start, $end];
                if ($action === 'add_worker') {
                    $pdo->prepare('INSERT INTO harbor_workers (port_id, employee_name, identity_number, nationality, worker_type, mobile_number, employment_status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$portId, ...$values]);
                    $message = 'تمت إضافة سجل القوى البشرية.';
                } else {
                    $pdo->prepare('UPDATE harbor_workers SET employee_name = ?, identity_number = ?, nationality = ?, worker_type = ?, mobile_number = ?, employment_status = ?, start_date = ?, end_date = ? WHERE id = ? AND port_id = ?')->execute([...$values, $workerId, $portId]);
                    $message = 'تم تحديث سجل القوى البشرية.';
                }
                redirectWithMessage(harborRedirect($portId, 'workers'), 'success', $message);

            case 'delete_worker':
                $workerId = (int)($_POST['record_id'] ?? 0); harborRecord($pdo, 'harbor_workers', $workerId, $portId);
                $pdo->prepare('DELETE FROM harbor_workers WHERE id = ? AND port_id = ?')->execute([$workerId, $portId]);
                redirectWithMessage(harborRedirect($portId, 'workers'), 'success', 'تم حذف سجل العامل.');

            case 'add_license':
            case 'update_license':
                $licenseId = (int)($_POST['record_id'] ?? 0);
                $existing = $action === 'update_license' ? harborRecord($pdo, 'harbor_licenses', $licenseId, $portId) : null;
                $number = trim((string)($_POST['license_number'] ?? '')); $holder = trim((string)($_POST['license_holder_name'] ?? ''));
                if ($number === '' || $holder === '') throw new InvalidArgumentException('رقم الرخصة واسم صاحبها مطلوبان.');
                $issue = harborNullable('issue_date'); $expiry = harborNullable('expiry_date');
                if ($issue && $expiry && $expiry < $issue) throw new InvalidArgumentException('تاريخ انتهاء الرخصة يجب ألا يسبق تاريخ إصدارها.');
                $pendingUpload = storeHarborAttachment('license_attachment', 'licenses');
                $attachment = $pendingUpload['path'] ?? ($existing['attachment_path'] ?? null);
                if ($existing && isset($_POST['remove_attachment']) && !$pendingUpload) $attachment = null;
                $values = [$number, harborEnum('license_type', ['seasonal','operational'], 'seasonal'), $holder, harborNullable('boat_number'), $issue, $expiry, harborEnum('license_status', ['valid','expired','suspended','cancelled'], 'valid'), $attachment];
                if ($action === 'add_license') {
                    $pdo->prepare('INSERT INTO harbor_licenses (port_id, license_number, license_type, license_holder_name, boat_number, issue_date, expiry_date, license_status, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$portId, ...$values]);
                    $message = 'تم تسجيل الرخصة.';
                } else {
                    $pdo->prepare('UPDATE harbor_licenses SET license_number = ?, license_type = ?, license_holder_name = ?, boat_number = ?, issue_date = ?, expiry_date = ?, license_status = ?, attachment_path = ? WHERE id = ? AND port_id = ?')->execute([...$values, $licenseId, $portId]);
                    if (($pendingUpload || isset($_POST['remove_attachment'])) && $existing['attachment_path'] !== $attachment) removeHarborAttachment($existing['attachment_path']);
                    $message = 'تم تحديث بيانات الرخصة.';
                }
                redirectWithMessage(harborRedirect($portId, 'licenses'), 'success', $message);

            case 'delete_license':
                $licenseId = (int)($_POST['record_id'] ?? 0); $existing = harborRecord($pdo, 'harbor_licenses', $licenseId, $portId);
                $pdo->prepare('DELETE FROM harbor_licenses WHERE id = ? AND port_id = ?')->execute([$licenseId, $portId]);
                removeHarborAttachment($existing['attachment_path']);
                redirectWithMessage(harborRedirect($portId, 'licenses'), 'success', 'تم حذف الرخصة ومرفقها.');

            case 'add_violation':
            case 'update_violation':
                $violationId = (int)($_POST['record_id'] ?? 0);
                $existing = $action === 'update_violation' ? harborRecord($pdo, 'harbor_violations', $violationId, $portId) : null;
                $number = trim((string)($_POST['violation_number'] ?? '')); $type = trim((string)($_POST['violation_type'] ?? ''));
                if ($number === '' || $type === '') throw new InvalidArgumentException('رقم المخالفة ونوعها مطلوبان.');
                $boatId = (int)($_POST['boat_id'] ?? 0) ?: null;
                if ($boatId) harborRecord($pdo, 'boats', $boatId, $portId);
                $date = harborNullable('violation_date') ?: date('Y-m-d H:i:s');
                $date = str_replace('T', ' ', $date);
                $pendingUpload = storeHarborAttachment('violation_attachment', 'violations');
                $attachment = $pendingUpload['path'] ?? ($existing['attachment_path'] ?? null);
                if ($existing && isset($_POST['remove_attachment']) && !$pendingUpload) $attachment = null;
                $fine = filter_var($_POST['fine_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
                if ($fine === false || $fine < 0) throw new InvalidArgumentException('قيمة الغرامة يجب أن تكون صفرًا أو رقمًا موجبًا.');
                $values = [$number, $type, harborNullable('violation_description'), $date, $boatId, harborNullable('boat_owner_name'), $fine, harborEnum('violation_status', ['open','paid','appealed','closed'], 'open'), $attachment];
                if ($action === 'add_violation') {
                    $pdo->prepare('INSERT INTO harbor_violations (port_id, violation_number, violation_type, violation_description, violation_date, boat_id, boat_owner_name, fine_amount, violation_status, created_by, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$portId, ...array_slice($values, 0, 8), (int)$currentUserData['id'], $attachment]);
                    $message = 'تم تسجيل المخالفة.';
                } else {
                    $pdo->prepare('UPDATE harbor_violations SET violation_number = ?, violation_type = ?, violation_description = ?, violation_date = ?, boat_id = ?, boat_owner_name = ?, fine_amount = ?, violation_status = ?, attachment_path = ? WHERE id = ? AND port_id = ?')->execute([...$values, $violationId, $portId]);
                    if (($pendingUpload || isset($_POST['remove_attachment'])) && $existing['attachment_path'] !== $attachment) removeHarborAttachment($existing['attachment_path']);
                    $message = 'تم تحديث بيانات المخالفة.';
                }
                redirectWithMessage(harborRedirect($portId, 'violations'), 'success', $message);

            case 'delete_violation':
                $violationId = (int)($_POST['record_id'] ?? 0); $existing = harborRecord($pdo, 'harbor_violations', $violationId, $portId);
                $pdo->prepare('DELETE FROM harbor_violations WHERE id = ? AND port_id = ?')->execute([$violationId, $portId]);
                removeHarborAttachment($existing['attachment_path']);
                redirectWithMessage(harborRedirect($portId, 'violations'), 'success', 'تم حذف المخالفة ومرفقها.');

            default:
                throw new InvalidArgumentException('الإجراء المطلوب غير معروف.');
        }
    } catch (Throwable $e) {
        if ($pendingUpload && is_file($pendingUpload['full_path'])) @unlink($pendingUpload['full_path']);
        error_log('Harbor details action: ' . $e->getMessage());
        $message = $e instanceof InvalidArgumentException ? $e->getMessage() : 'تعذر حفظ البيانات. قد يكون الرقم مكررًا أو أن السجل مرتبط ببيانات تشغيلية أخرى.';
        redirectWithMessage($redirectUrl, 'error', $message);
    }
}

$harbor = null;
$boatTypes = [
    'large' => ['label' => 'قوارب كبيرة', 'capacity' => 0, 'occupied' => 0, 'disabled' => 0, 'status' => 'available'],
    'small' => ['label' => 'قوارب صغيرة', 'capacity' => 0, 'occupied' => 0, 'disabled' => 0, 'status' => 'available'],
    'recreational' => ['label' => 'قوارب نزهة', 'capacity' => 0, 'occupied' => 0, 'disabled' => 0, 'status' => 'available'],
];
$boats = $workers = $licenses = $violations = [];
if ($portId) {
    foreach ($portsList as $item) if ((int)$item['id'] === $portId) { $harbor = $item; break; }
    $stmt = $pdo->prepare('SELECT * FROM harbor_boat_capacities WHERE port_id = ?'); $stmt->execute([$portId]);
    foreach ($stmt->fetchAll() as $row) { $boatTypes[$row['boat_type']]['capacity'] = (int)$row['capacity']; $boatTypes[$row['boat_type']]['status'] = $row['status']; }
    $stmt = $pdo->prepare('SELECT * FROM boats WHERE home_port_id = ? ORDER BY boat_type, name'); $stmt->execute([$portId]); $boats = $stmt->fetchAll();
    foreach ($boats as $boat) {
        if (!isset($boatTypes[$boat['boat_type']])) continue;
        if ($boat['harbor_status'] === 'occupied') $boatTypes[$boat['boat_type']]['occupied']++;
        if ($boat['harbor_status'] === 'disabled') $boatTypes[$boat['boat_type']]['disabled']++;
    }
    foreach ($boatTypes as &$type) {
        $type['available'] = max(0, $type['capacity'] - $type['occupied']);
        $type['percent'] = harborPercent($type['occupied'], $type['capacity']);
        if ($type['status'] !== 'stopped') $type['status'] = $type['capacity'] > 0 && $type['occupied'] >= $type['capacity'] ? 'full' : 'available';
    }
    unset($type);
    $stmt = $pdo->prepare('SELECT * FROM harbor_workers WHERE port_id = ? ORDER BY created_at DESC'); $stmt->execute([$portId]); $workers = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT * FROM harbor_licenses WHERE port_id = ? ORDER BY created_at DESC'); $stmt->execute([$portId]); $licenses = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT v.*, b.name AS boat_name FROM harbor_violations v LEFT JOIN boats b ON b.id = v.boat_id WHERE v.port_id = ? ORDER BY v.violation_date DESC'); $stmt->execute([$portId]); $violations = $stmt->fetchAll();
}

$totalCapacity = array_sum(array_column($boatTypes, 'capacity'));
$totalOccupied = array_sum(array_column($boatTypes, 'occupied'));
$totalDisabled = array_sum(array_column($boatTypes, 'disabled'));
$totalAvailable = max(0, $totalCapacity - $totalOccupied);
$occupancyRate = harborPercent($totalOccupied, $totalCapacity);
$activeWorkers = count(array_filter($workers, fn($row) => $row['employment_status'] === 'active'));
$validLicenses = count(array_filter($licenses, fn($row) => $row['license_status'] === 'valid'));
$openViolations = count(array_filter($violations, fn($row) => in_array($row['violation_status'], ['open','appealed'], true)));
$typeLabels = ['large'=>'كبير','small'=>'صغير','recreational'=>'نزهة','unclassified'=>'غير مصنف'];
$boatStatusLabels = ['occupied'=>'شاغل','disabled'=>'معطل','inactive'=>'غير نشط','unclassified'=>'غير مصنف'];
$workerTypeLabels = ['supervisor'=>'مشرف','contractor'=>'متعاقد','fisherman'=>'صياد','foreign_worker'=>'عامل أجنبي'];
$employmentLabels = ['active'=>'نشط','suspended'=>'موقوف','expired'=>'منتهي'];
$licenseStatusLabels = ['valid'=>'سارية','expired'=>'منتهية','suspended'=>'معلقة','cancelled'=>'ملغاة'];
$violationStatusLabels = ['open'=>'مفتوحة','paid'=>'مسددة','appealed'=>'معترض عليها','closed'=>'مغلقة'];

if ($portId && ($_GET['export'] ?? '') === 'csv') {
    $safeName = preg_replace('/[^\p{L}\p{N}_-]+/u', '-', $harbor['name']);
    header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="harbor-' . rawurlencode($safeName) . '.csv"'); echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['تقرير المرفأ', $harbor['name']]); fputcsv($out, ['المنطقة', $harbor['region_name'], 'المحافظة', $harbor['governorate_name'], 'الموقع', $harbor['location_name']]);
    fputcsv($out, ['الاستيعاب', $totalCapacity, 'الشاغلة', $totalOccupied, 'المعطلة', $totalDisabled, 'المتاحة', $totalAvailable, 'التشغيل', $occupancyRate . '%']);
    fputcsv($out, []); fputcsv($out, ['القارب','رقم التسجيل','النوع','الحالة']); foreach ($boats as $row) fputcsv($out, [$row['name'],$row['registration_no'],$typeLabels[$row['boat_type']],$boatStatusLabels[$row['harbor_status']]]);
    fputcsv($out, []); fputcsv($out, ['العامل','الفئة','الجنسية','الجوال','الحالة','البداية','النهاية']); foreach ($workers as $row) fputcsv($out, [$row['employee_name'],$workerTypeLabels[$row['worker_type']],$row['nationality']==='saudi'?'سعودي':'غير سعودي',$row['mobile_number'],$employmentLabels[$row['employment_status']],$row['start_date'],$row['end_date']]);
    fputcsv($out, []); fputcsv($out, ['الرخصة','النوع','صاحب الرخصة','رقم القارب','الإصدار','الانتهاء','الحالة']); foreach ($licenses as $row) fputcsv($out, [$row['license_number'],$row['license_type']==='seasonal'?'موسمية':'تشغيلية',$row['license_holder_name'],$row['boat_number'],$row['issue_date'],$row['expiry_date'],$licenseStatusLabels[$row['license_status']]]);
    fputcsv($out, []); fputcsv($out, ['المخالفة','النوع','التاريخ','القارب','المالك','الغرامة','الحالة','الوصف']); foreach ($violations as $row) fputcsv($out, [$row['violation_number'],$row['violation_type'],$row['violation_date'],$row['boat_name'],$row['boat_owner_name'],$row['fine_amount'],$violationStatusLabels[$row['violation_status']],$row['violation_description']]);
    fclose($out); exit;
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="harbor-workspace">
<?php if (!$portId || !$harbor): ?>
    <section class="harbor-empty">
        <span class="harbor-kicker">سجل المرافئ</span>
        <h1>لا يوجد مرفأ ضمن نطاق صلاحيتك</h1>
        <p>أنشئ أول مرفأ لبدء تسجيل الطاقة الاستيعابية والقوارب والقوى البشرية والتراخيص.</p>
        <?php if ($canCreateHarbor): ?><button class="btn btn-primary" type="button" data-dialog="createHarborDialog">إنشاء مرفأ جديد</button><?php endif; ?>
    </section>
<?php else: ?>
    <header class="harbor-commandbar">
        <div class="harbor-titleblock">
            <div class="harbor-kicker"><span>إدارة المرافئ</span><b>HBR–<?= str_pad((string)$portId, 4, '0', STR_PAD_LEFT) ?></b></div>
            <h1><?= e($harbor['name']) ?></h1>
            <p><?= e($harbor['region_name']) ?> · <?= e($harbor['governorate_name']) ?><?php if ($harbor['location_name']): ?> · <?= e($harbor['location_name']) ?><?php endif; ?></p>
        </div>
        <div class="harbor-command-actions">
            <?php if (count($portsList) > 1): ?>
            <form method="get" class="harbor-picker"><label for="harborSelect">المرفأ</label><select id="harborSelect" name="port_id" onchange="this.form.submit()"><?php foreach ($portsList as $port): ?><option value="<?= (int)$port['id'] ?>" <?= (int)$port['id'] === $portId ? 'selected' : '' ?>><?= e($port['name']) ?> — <?= e($port['governorate_name']) ?></option><?php endforeach; ?></select></form>
            <?php endif; ?>
            <?php if ($canCreateHarbor): ?><button class="harbor-icon-button" type="button" data-dialog="createHarborDialog" title="إنشاء مرفأ" aria-label="إنشاء مرفأ">＋</button><?php endif; ?>
            <button class="harbor-icon-button" type="button" data-dialog="editHarborDialog" title="تعديل المرفأ" aria-label="تعديل المرفأ">✎</button>
            <a class="harbor-icon-button" href="?port_id=<?= $portId ?>&amp;export=csv" title="تصدير CSV" aria-label="تصدير CSV">⇩</a>
        </div>
    </header>

    <section class="harbor-vitals" aria-label="ملخص المرفأ">
        <article class="harbor-vital harbor-vital-primary"><small>نسبة الإشغال</small><strong><?= numberAr($occupancyRate, 1) ?><em>%</em></strong><div class="harbor-meter"><i style="width:<?= $occupancyRate ?>%"></i></div><span><?= numberAr($totalOccupied) ?> من <?= numberAr($totalCapacity) ?> رصيف</span></article>
        <article class="harbor-vital"><small>الأماكن المتاحة</small><strong><?= numberAr($totalAvailable) ?></strong><span>إجمالي الطاقة المتبقية</span></article>
        <article class="harbor-vital"><small>القوارب المعطلة</small><strong><?= numberAr($totalDisabled) ?></strong><span>تحتاج متابعة تشغيلية</span></article>
        <article class="harbor-vital"><small>القوى العاملة</small><strong><?= numberAr($activeWorkers) ?></strong><span>نشط من <?= numberAr(count($workers)) ?> سجل</span></article>
        <article class="harbor-vital"><small>الرخص السارية</small><strong><?= numberAr($validLicenses) ?></strong><span>من <?= numberAr(count($licenses)) ?> رخصة</span></article>
        <article class="harbor-vital <?= $openViolations ? 'is-alert' : '' ?>"><small>مخالفات قيد المتابعة</small><strong><?= numberAr($openViolations) ?></strong><span>من <?= numberAr(count($violations)) ?> مخالفة</span></article>
    </section>

    <section class="harbor-profile">
        <div class="harbor-section-head"><div><span>01 / ملف المرفأ</span><h2>الهوية والموقع</h2></div><button class="btn btn-outline btn-sm" type="button" data-dialog="editHarborDialog">تعديل البيانات</button></div>
        <div class="harbor-profile-grid">
            <dl><dt>المنطقة</dt><dd><?= e($harbor['region_name']) ?></dd></dl><dl><dt>المحافظة</dt><dd><?= e($harbor['governorate_name']) ?></dd></dl><dl><dt>الموقع الوصفي</dt><dd><?= e($harbor['location_name'] ?: 'غير مسجل') ?></dd></dl><dl><dt>حالة التشغيل</dt><dd><span class="harbor-status <?= $harbor['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $harbor['is_active'] ? 'نشط' : 'متوقف' ?></span></dd></dl><dl><dt>الإحداثيات</dt><dd dir="ltr"><?= $harbor['latitude'] !== null && $harbor['longitude'] !== null ? e($harbor['latitude'] . ', ' . $harbor['longitude']) : '—' ?></dd></dl><dl><dt>رابط الموقع</dt><dd><?php if ($harbor['location_url']): ?><a href="<?= e($harbor['location_url']) ?>" target="_blank" rel="noopener">فتح الخريطة ↗</a><?php else: ?>غير مسجل<?php endif; ?></dd></dl>
        </div>
    </section>

    <section class="harbor-capacity">
        <div class="harbor-section-head"><div><span>02 / الطاقة الاستيعابية</span><h2>حالة الأرصفة حسب نوع القارب</h2></div><button class="btn btn-outline btn-sm" type="button" data-dialog="capacityDialog">ضبط السعات</button></div>
        <div class="harbor-capacity-grid">
        <?php foreach ($boatTypes as $key => $type): ?>
            <article class="capacity-card <?= $type['status'] === 'stopped' ? 'is-stopped' : '' ?>">
                <div class="capacity-card-top"><div><small><?= e($type['label']) ?></small><b><?= $type['status'] === 'stopped' ? 'الرصيف متوقف' : ($type['status'] === 'full' ? 'مكتمل الإشغال' : 'متاح للتشغيل') ?></b></div><strong><?= numberAr($type['percent'], 1) ?><em>%</em></strong></div>
                <div class="harbor-meter"><i style="width:<?= $type['percent'] ?>%"></i></div>
                <div class="capacity-numbers"><span><b><?= numberAr($type['capacity']) ?></b> الاستيعاب</span><span><b><?= numberAr($type['occupied']) ?></b> شاغل</span><span><b><?= numberAr($type['available']) ?></b> متاح</span><span><b><?= numberAr($type['disabled']) ?></b> معطل</span></div>
            </article>
        <?php endforeach; ?>
        </div>
    </section>

    <section class="harbor-register">
        <div class="harbor-register-head"><div><span>03 / السجلات التشغيلية</span><h2>إدارة بيانات المرفأ</h2></div><label class="harbor-search"><span>⌕</span><input type="search" id="harborRecordSearch" placeholder="ابحث في السجل الحالي..." autocomplete="off"></label></div>
        <div class="harbor-tabs" role="tablist" aria-label="سجلات المرفأ">
            <button type="button" role="tab" data-tab="boats"><span>القوارب</span><b><?= numberAr(count($boats)) ?></b></button>
            <button type="button" role="tab" data-tab="workers"><span>القوى البشرية</span><b><?= numberAr(count($workers)) ?></b></button>
            <button type="button" role="tab" data-tab="licenses"><span>التراخيص</span><b><?= numberAr(count($licenses)) ?></b></button>
            <button type="button" role="tab" data-tab="violations"><span>المخالفات</span><b><?= numberAr(count($violations)) ?></b></button>
        </div>

        <div class="harbor-tab-panel" data-panel="boats">
            <div class="register-toolbar"><p>القوارب المسجلة والمرتبطة بهذا المرفأ.</p><button class="btn btn-primary btn-sm" type="button" data-dialog="boatDialog" data-mode="add">＋ إضافة قارب</button></div>
            <div class="harbor-table-wrap"><table class="harbor-table"><thead><tr><th>القارب</th><th>رقم التسجيل</th><th>النوع</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
            <?php if (!$boats): ?><tr class="empty-row"><td colspan="5">لا توجد قوارب مسجلة. ابدأ بإضافة أول قارب.</td></tr><?php else: foreach ($boats as $row): ?>
                <tr data-search-row><td><strong><?= e($row['name']) ?></strong><small>#<?= (int)$row['id'] ?></small></td><td dir="ltr"><?= e($row['registration_no'] ?: '—') ?></td><td><?= e($typeLabels[$row['boat_type']]) ?></td><td><span class="harbor-status status-<?= e($row['harbor_status']) ?>"><?= e($boatStatusLabels[$row['harbor_status']]) ?></span></td><td><div class="row-actions"><button type="button" class="row-action edit" data-edit-boat='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>تعديل</button><form method="post" data-confirm="سيتم حذف القارب نهائيًا. هل تريد المتابعة؟"><?= csrfField() ?><input type="hidden" name="action" value="delete_boat"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="return_tab" value="boats"><button class="row-action delete" type="submit">حذف</button></form></div></td></tr>
            <?php endforeach; endif; ?></tbody></table></div>
        </div>

        <div class="harbor-tab-panel" data-panel="workers">
            <div class="register-toolbar"><p>المشرفون والمتعاقدون والصيادون والعاملون بالمرفأ.</p><button class="btn btn-primary btn-sm" type="button" data-dialog="workerDialog" data-mode="add">＋ إضافة عامل</button></div>
            <div class="harbor-table-wrap"><table class="harbor-table"><thead><tr><th>الاسم</th><th>الفئة</th><th>الجنسية</th><th>الجوال</th><th>المدة</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
            <?php if (!$workers): ?><tr class="empty-row"><td colspan="7">لا توجد سجلات قوى بشرية.</td></tr><?php else: foreach ($workers as $row): ?>
                <tr data-search-row><td><strong><?= e($row['employee_name']) ?></strong><small><?= $row['identity_number'] ? 'الهوية محفوظة بأمان' : 'لا توجد هوية' ?></small></td><td><?= e($workerTypeLabels[$row['worker_type']]) ?></td><td><?= $row['nationality'] === 'saudi' ? 'سعودي' : 'غير سعودي' ?></td><td dir="ltr"><?= e($row['mobile_number'] ?: '—') ?></td><td><span><?= e($row['start_date'] ?: '—') ?></span><small>إلى <?= e($row['end_date'] ?: 'مفتوح') ?></small></td><td><span class="harbor-status status-<?= e($row['employment_status']) ?>"><?= e($employmentLabels[$row['employment_status']]) ?></span></td><td><div class="row-actions"><button type="button" class="row-action edit" data-edit-worker='<?= e(json_encode(array_diff_key($row, ['identity_number' => true]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>تعديل</button><form method="post" data-confirm="سيتم حذف سجل العامل نهائيًا. هل تريد المتابعة؟"><?= csrfField() ?><input type="hidden" name="action" value="delete_worker"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="return_tab" value="workers"><button class="row-action delete" type="submit">حذف</button></form></div></td></tr>
            <?php endforeach; endif; ?></tbody></table></div>
        </div>

        <div class="harbor-tab-panel" data-panel="licenses">
            <div class="register-toolbar"><p>الرخص الموسمية والتشغيلية مع تواريخها ومرفقاتها.</p><button class="btn btn-primary btn-sm" type="button" data-dialog="licenseDialog" data-mode="add">＋ إضافة رخصة</button></div>
            <div class="harbor-table-wrap"><table class="harbor-table"><thead><tr><th>رقم الرخصة</th><th>النوع / صاحب الرخصة</th><th>القارب</th><th>الصلاحية</th><th>الحالة</th><th>المرفق</th><th>الإجراء</th></tr></thead><tbody>
            <?php if (!$licenses): ?><tr class="empty-row"><td colspan="7">لا توجد تراخيص مسجلة.</td></tr><?php else: foreach ($licenses as $row): ?>
                <tr data-search-row><td dir="ltr"><strong><?= e($row['license_number']) ?></strong></td><td><strong><?= e($row['license_holder_name']) ?></strong><small><?= $row['license_type'] === 'seasonal' ? 'موسمية' : 'تشغيلية' ?></small></td><td dir="ltr"><?= e($row['boat_number'] ?: '—') ?></td><td><span><?= e($row['issue_date'] ?: '—') ?></span><small>إلى <?= e($row['expiry_date'] ?: 'غير محدد') ?></small></td><td><span class="harbor-status status-<?= e($row['license_status']) ?>"><?= e($licenseStatusLabels[$row['license_status']]) ?></span></td><td><?php if ($row['attachment_path']): ?><a class="attachment-link" href="?port_id=<?= $portId ?>&amp;attachment=license&amp;attachment_id=<?= (int)$row['id'] ?>">تنزيل ↙</a><?php else: ?>—<?php endif; ?></td><td><div class="row-actions"><button type="button" class="row-action edit" data-edit-license='<?= e(json_encode(array_merge($row, ['attachment_path' => (bool)$row['attachment_path']]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>تعديل</button><form method="post" data-confirm="سيتم حذف الرخصة ومرفقها نهائيًا. هل تريد المتابعة؟"><?= csrfField() ?><input type="hidden" name="action" value="delete_license"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="return_tab" value="licenses"><button class="row-action delete" type="submit">حذف</button></form></div></td></tr>
            <?php endforeach; endif; ?></tbody></table></div>
        </div>

        <div class="harbor-tab-panel" data-panel="violations">
            <div class="register-toolbar"><p>المخالفات والغرامات وحالة المتابعة.</p><button class="btn btn-primary btn-sm" type="button" data-dialog="violationDialog" data-mode="add">＋ إضافة مخالفة</button></div>
            <div class="harbor-table-wrap"><table class="harbor-table"><thead><tr><th>المخالفة</th><th>النوع / الوصف</th><th>القارب / المالك</th><th>التاريخ</th><th>الغرامة</th><th>الحالة</th><th>المرفق</th><th>الإجراء</th></tr></thead><tbody>
            <?php if (!$violations): ?><tr class="empty-row"><td colspan="8">لا توجد مخالفات مسجلة.</td></tr><?php else: foreach ($violations as $row): ?>
                <tr data-search-row><td dir="ltr"><strong><?= e($row['violation_number']) ?></strong></td><td><strong><?= e($row['violation_type']) ?></strong><small><?= e($row['violation_description'] ?: 'بدون وصف') ?></small></td><td><span><?= e($row['boat_name'] ?: 'غير محدد') ?></span><small><?= e($row['boat_owner_name'] ?: 'المالك غير مسجل') ?></small></td><td><?= e(date('Y-m-d H:i', strtotime($row['violation_date']))) ?></td><td><strong><?= numberAr($row['fine_amount'], 2) ?></strong></td><td><span class="harbor-status status-<?= e($row['violation_status']) ?>"><?= e($violationStatusLabels[$row['violation_status']]) ?></span></td><td><?php if ($row['attachment_path']): ?><a class="attachment-link" href="?port_id=<?= $portId ?>&amp;attachment=violation&amp;attachment_id=<?= (int)$row['id'] ?>">تنزيل ↙</a><?php else: ?>—<?php endif; ?></td><td><div class="row-actions"><button type="button" class="row-action edit" data-edit-violation='<?= e(json_encode(array_merge($row, ['attachment_path' => (bool)$row['attachment_path']]), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>تعديل</button><form method="post" data-confirm="سيتم حذف المخالفة ومرفقها نهائيًا. هل تريد المتابعة؟"><?= csrfField() ?><input type="hidden" name="action" value="delete_violation"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="return_tab" value="violations"><button class="row-action delete" type="submit">حذف</button></form></div></td></tr>
            <?php endforeach; endif; ?></tbody></table></div>
        </div>
    </section>

    <?php if ($canDeleteHarbor): ?><section class="harbor-danger-zone"><div><span>منطقة حساسة</span><h2>حذف سجل المرفأ</h2><p>يتوقف الحذف إذا كان المرفأ مرتبطًا برحلات أو بيانات تشغيلية يجب الاحتفاظ بها.</p></div><button class="btn btn-danger" type="button" data-dialog="deleteHarborDialog">حذف المرفأ</button></section><?php endif; ?>
<?php endif; ?>
</div>

<?php if ($canCreateHarbor): ?>
<dialog id="createHarborDialog" class="harbor-dialog"><form method="post"><div class="dialog-head"><div><small>مرفأ جديد</small><h3>إنشاء سجل مرفأ متكامل</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="create_port"><div class="harbor-form-grid"><label>اسم المرفأ <b>*</b><input name="name" required maxlength="150"></label><label>المحافظة <b>*</b><select name="governorate_id" required><option value="">اختر المحافظة</option><?php foreach ($governoratesList as $gov): ?><option value="<?= (int)$gov['id'] ?>"><?= e($gov['region_name']) ?> — <?= e($gov['name']) ?></option><?php endforeach; ?></select></label><label class="span-2">وصف الموقع<input name="location_name" maxlength="190" placeholder="مثال: الواجهة البحرية، الرصيف الشمالي"></label><label class="span-2">رابط الموقع<input type="url" name="location_url" maxlength="500" placeholder="https://maps.google.com/..."></label><label>خط العرض<input type="number" name="latitude" min="-90" max="90" step="0.000001" dir="ltr"></label><label>خط الطول<input type="number" name="longitude" min="-180" max="180" step="0.000001" dir="ltr"></label><label class="check-field span-2"><input type="checkbox" name="is_active" checked><span>المرفأ نشط وجاهز للتشغيل</span></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit">إنشاء المرفأ</button></div></form></dialog>
<?php endif; ?>

<?php if ($harbor): ?>
<dialog id="editHarborDialog" class="harbor-dialog"><form method="post"><div class="dialog-head"><div><small>ملف المرفأ</small><h3>تعديل البيانات الأساسية</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="update_port"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="harbor-form-grid"><label>اسم المرفأ <b>*</b><input name="name" value="<?= e($harbor['name']) ?>" required maxlength="150"></label><label>المحافظة <b>*</b><select name="governorate_id" required><?php foreach ($governoratesList as $gov): ?><option value="<?= (int)$gov['id'] ?>" <?= (int)$gov['id']===(int)$harbor['governorate_id']?'selected':'' ?>><?= e($gov['region_name']) ?> — <?= e($gov['name']) ?></option><?php endforeach; ?></select></label><label class="span-2">وصف الموقع<input name="location_name" value="<?= e($harbor['location_name']) ?>" maxlength="190"></label><label class="span-2">رابط الموقع<input type="url" name="location_url" value="<?= e($harbor['location_url']) ?>" maxlength="500"></label><label>خط العرض<input type="number" name="latitude" value="<?= e($harbor['latitude']) ?>" min="-90" max="90" step="0.000001" dir="ltr"></label><label>خط الطول<input type="number" name="longitude" value="<?= e($harbor['longitude']) ?>" min="-180" max="180" step="0.000001" dir="ltr"></label><label class="check-field span-2"><input type="checkbox" name="is_active" <?= $harbor['is_active'] ? 'checked' : '' ?>><span>المرفأ نشط وجاهز للتشغيل</span></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit">حفظ التغييرات</button></div></form></dialog>

<dialog id="capacityDialog" class="harbor-dialog harbor-dialog-wide"><form method="post"><div class="dialog-head"><div><small>الطاقة الاستيعابية</small><h3>ضبط سعات الأرصفة</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="update_capacities"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="capacity-form-grid"><?php foreach ($boatTypes as $key => $type): ?><fieldset><legend><?= e($type['label']) ?></legend><label>السعة القصوى<input type="number" min="0" name="capacity_<?= $key ?>" value="<?= (int)$type['capacity'] ?>" required></label><label>حالة الرصيف<select name="status_<?= $key ?>"><option value="available" <?= $type['status'] !== 'stopped' ? 'selected' : '' ?>>متاح</option><option value="stopped" <?= $type['status'] === 'stopped' ? 'selected' : '' ?>>متوقف</option></select></label><small>الإشغال الحالي محسوب تلقائيًا من القوارب الشاغلة.</small></fieldset><?php endforeach; ?></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit">حفظ السعات</button></div></form></dialog>

<dialog id="boatDialog" class="harbor-dialog"><form method="post" data-crud-form><div class="dialog-head"><div><small>سجل القوارب</small><h3 data-form-title>إضافة قارب</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_boat"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id"><input type="hidden" name="return_tab" value="boats"><div class="harbor-form-grid"><label class="span-2">اسم القارب <b>*</b><input name="boat_name" required maxlength="150"></label><label>رقم التسجيل<input name="registration_no" maxlength="50" dir="ltr"></label><label>النوع<select name="boat_type"><option value="large">كبير</option><option value="small">صغير</option><option value="recreational">نزهة</option><option value="unclassified">غير مصنف</option></select></label><label class="span-2">الحالة داخل المرفأ<select name="harbor_status"><option value="occupied">شاغل</option><option value="disabled">معطل</option><option value="inactive">غير نشط</option><option value="unclassified">غير مصنف</option></select></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit" data-submit-label>إضافة القارب</button></div></form></dialog>

<dialog id="workerDialog" class="harbor-dialog"><form method="post" data-crud-form><div class="dialog-head"><div><small>القوى البشرية</small><h3 data-form-title>إضافة عامل</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_worker"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id"><input type="hidden" name="return_tab" value="workers"><div class="harbor-form-grid"><label class="span-2">الاسم الكامل <b>*</b><input name="employee_name" required maxlength="150"></label><label>الهوية أو الإقامة<input name="identity_number" maxlength="30" autocomplete="off" dir="ltr"><small data-identity-note>تُحفظ مشفرة ولا تظهر بعد الحفظ.</small></label><label>رقم الجوال<input name="mobile_number" maxlength="30" dir="ltr"></label><label>الجنسية<select name="nationality"><option value="saudi">سعودي</option><option value="non_saudi">غير سعودي</option></select></label><label>الفئة<select name="worker_type"><option value="supervisor">مشرف</option><option value="contractor">متعاقد</option><option value="fisherman">صياد</option><option value="foreign_worker">عامل أجنبي</option></select></label><label>الحالة<select name="employment_status"><option value="active">نشط</option><option value="suspended">موقوف</option><option value="expired">منتهي</option></select></label><label>تاريخ البداية<input type="date" name="start_date"></label><label>تاريخ الانتهاء<input type="date" name="end_date"></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit" data-submit-label>إضافة العامل</button></div></form></dialog>

<dialog id="licenseDialog" class="harbor-dialog"><form method="post" enctype="multipart/form-data" data-crud-form><div class="dialog-head"><div><small>التراخيص</small><h3 data-form-title>إضافة رخصة</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_license"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id"><input type="hidden" name="return_tab" value="licenses"><div class="harbor-form-grid"><label>رقم الرخصة <b>*</b><input name="license_number" required maxlength="80" dir="ltr"></label><label>نوع الرخصة<select name="license_type"><option value="seasonal">موسمية</option><option value="operational">تشغيلية</option></select></label><label class="span-2">اسم صاحب الرخصة <b>*</b><input name="license_holder_name" required maxlength="190"></label><label>رقم القارب<input name="boat_number" maxlength="80" dir="ltr"></label><label>الحالة<select name="license_status"><option value="valid">سارية</option><option value="expired">منتهية</option><option value="suspended">معلقة</option><option value="cancelled">ملغاة</option></select></label><label>تاريخ الإصدار<input type="date" name="issue_date"></label><label>تاريخ الانتهاء<input type="date" name="expiry_date"></label><label class="span-2 file-field">نسخة الرخصة<input type="file" name="license_attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"><small>PDF أو صورة، بحد أقصى 10 ميجابايت.</small></label><label class="check-field span-2 attachment-removal" hidden><input type="checkbox" name="remove_attachment"><span>حذف المرفق الحالي دون استبداله</span></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit" data-submit-label>تسجيل الرخصة</button></div></form></dialog>

<dialog id="violationDialog" class="harbor-dialog"><form method="post" enctype="multipart/form-data" data-crud-form><div class="dialog-head"><div><small>المخالفات</small><h3 data-form-title>إضافة مخالفة</h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_violation"><input type="hidden" name="port_id" value="<?= $portId ?>"><input type="hidden" name="record_id"><input type="hidden" name="return_tab" value="violations"><div class="harbor-form-grid"><label>رقم المخالفة <b>*</b><input name="violation_number" required maxlength="80" dir="ltr"></label><label>نوع المخالفة <b>*</b><input name="violation_type" required maxlength="120"></label><label>القارب<select name="boat_id"><option value="">غير محدد</option><?php foreach ($boats as $boat): ?><option value="<?= (int)$boat['id'] ?>"><?= e($boat['name']) ?><?= $boat['registration_no'] ? ' — ' . e($boat['registration_no']) : '' ?></option><?php endforeach; ?></select></label><label>اسم صاحب القارب<input name="boat_owner_name" maxlength="190"></label><label>تاريخ المخالفة<input type="datetime-local" name="violation_date"></label><label>قيمة الغرامة<input type="number" min="0" step="0.01" name="fine_amount" value="0"></label><label class="span-2">الحالة<select name="violation_status"><option value="open">مفتوحة</option><option value="paid">مسددة</option><option value="appealed">معترض عليها</option><option value="closed">مغلقة</option></select></label><label class="span-2">الوصف الكامل<textarea name="violation_description" rows="4"></textarea></label><label class="span-2 file-field">المحضر أو الصورة<input type="file" name="violation_attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"><small>PDF أو صورة، بحد أقصى 10 ميجابايت.</small></label><label class="check-field span-2 attachment-removal" hidden><input type="checkbox" name="remove_attachment"><span>حذف المرفق الحالي دون استبداله</span></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-primary" type="submit" data-submit-label>تسجيل المخالفة</button></div></form></dialog>

<?php if ($canDeleteHarbor): ?><dialog id="deleteHarborDialog" class="harbor-dialog harbor-dialog-danger"><form method="post"><div class="dialog-head"><div><small>إجراء غير قابل للتراجع</small><h3>حذف <?= e($harbor['name']) ?></h3></div><button type="button" data-close aria-label="إغلاق">×</button></div><?= csrfField() ?><input type="hidden" name="action" value="delete_port"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="delete-warning"><b>قبل المتابعة</b><p>لن يُحذف المرفأ إذا كان مرتبطًا برحلات أو سجلات نظام ملزمة. اكتب اسم المرفأ كما يظهر أدناه للتأكيد.</p><code><?= e($harbor['name']) ?></code><label>اسم المرفأ<input name="confirm_name" required autocomplete="off"></label></div><div class="dialog-footer"><button type="button" class="btn btn-outline" data-close>إلغاء</button><button class="btn btn-danger" type="submit">حذف نهائي</button></div></form></dialog><?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var currentTab = new URLSearchParams(location.search).get('tab') || 'boats';
    var tabs = Array.from(document.querySelectorAll('[data-tab]'));
    var panels = Array.from(document.querySelectorAll('[data-panel]'));
    function activateTab(name) {
        if (!panels.some(function (panel) { return panel.dataset.panel === name; })) name = 'boats';
        tabs.forEach(function (tab) { var active = tab.dataset.tab === name; tab.classList.toggle('active', active); tab.setAttribute('aria-selected', active ? 'true' : 'false'); });
        panels.forEach(function (panel) { panel.hidden = panel.dataset.panel !== name; });
        currentTab = name;
        var search = document.getElementById('harborRecordSearch'); if (search) { search.value = ''; filterRows(''); }
    }
    tabs.forEach(function (tab) { tab.addEventListener('click', function () { activateTab(tab.dataset.tab); history.replaceState(null, '', location.pathname + '?port_id=<?= $portId ?>&tab=' + tab.dataset.tab); }); });
    activateTab(currentTab);

    function filterRows(query) {
        var activePanel = document.querySelector('[data-panel="' + currentTab + '"]'); if (!activePanel) return;
        activePanel.querySelectorAll('[data-search-row]').forEach(function (row) { row.hidden = query && !row.textContent.toLocaleLowerCase('ar').includes(query); });
    }
    var search = document.getElementById('harborRecordSearch'); if (search) search.addEventListener('input', function () { filterRows(search.value.trim().toLocaleLowerCase('ar')); });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) { form.addEventListener('submit', function (event) { if (!confirm(form.dataset.confirm)) event.preventDefault(); }); });

    function resetCrud(dialog, addAction, title, submitLabel) {
        var form = dialog.querySelector('form'); form.reset(); form.elements.action.value = addAction; form.elements.record_id.value = '';
        dialog.querySelector('[data-form-title]').textContent = title; dialog.querySelector('[data-submit-label]').textContent = submitLabel;
        dialog.querySelectorAll('.attachment-removal').forEach(function (field) { field.hidden = true; });
        var identityNote = dialog.querySelector('[data-identity-note]'); if (identityNote) identityNote.textContent = 'تُحفظ مشفرة ولا تظهر بعد الحفظ.';
    }
    function fill(form, values) { Object.keys(values).forEach(function (key) { if (form.elements[key] && values[key] !== null) form.elements[key].value = values[key]; }); }
    function openEdit(dialogId, action, title, submitLabel, data, values) {
        var dialog = document.getElementById(dialogId); resetCrud(dialog, action, title, submitLabel); var form = dialog.querySelector('form');
        form.elements.action.value = action; form.elements.record_id.value = data.id; fill(form, values);
        var removal = dialog.querySelector('.attachment-removal'); if (removal) removal.hidden = !data.attachment_path;
        dialog.showModal();
    }
    document.querySelectorAll('[data-dialog][data-mode="add"]').forEach(function (button) { button.addEventListener('click', function () {
        var dialog = document.getElementById(button.dataset.dialog);
        var settings = {boatDialog:['add_boat','إضافة قارب','إضافة القارب'],workerDialog:['add_worker','إضافة عامل','إضافة العامل'],licenseDialog:['add_license','إضافة رخصة','تسجيل الرخصة'],violationDialog:['add_violation','إضافة مخالفة','تسجيل المخالفة']}[button.dataset.dialog];
        if (dialog && settings) { resetCrud(dialog, settings[0], settings[1], settings[2]); if (button.dataset.dialog === 'violationDialog') dialog.querySelector('[name="violation_date"]').value = new Date(Date.now() - new Date().getTimezoneOffset()*60000).toISOString().slice(0,16); }
    }); });
    document.querySelectorAll('[data-edit-boat]').forEach(function (button) { button.addEventListener('click', function () { var d=JSON.parse(button.dataset.editBoat); openEdit('boatDialog','update_boat','تعديل القارب','حفظ التعديلات',d,{boat_name:d.name,registration_no:d.registration_no||'',boat_type:d.boat_type,harbor_status:d.harbor_status}); }); });
    document.querySelectorAll('[data-edit-worker]').forEach(function (button) { button.addEventListener('click', function () { var d=JSON.parse(button.dataset.editWorker); openEdit('workerDialog','update_worker','تعديل سجل العامل','حفظ التعديلات',d,{employee_name:d.employee_name,mobile_number:d.mobile_number||'',nationality:d.nationality,worker_type:d.worker_type,employment_status:d.employment_status,start_date:d.start_date||'',end_date:d.end_date||''}); var note=document.querySelector('#workerDialog [data-identity-note]'); if(note) note.textContent='اترك الحقل فارغًا للاحتفاظ بالهوية المشفرة الحالية.'; }); });
    document.querySelectorAll('[data-edit-license]').forEach(function (button) { button.addEventListener('click', function () { var d=JSON.parse(button.dataset.editLicense); openEdit('licenseDialog','update_license','تعديل الرخصة','حفظ التعديلات',d,{license_number:d.license_number,license_type:d.license_type,license_holder_name:d.license_holder_name,boat_number:d.boat_number||'',issue_date:d.issue_date||'',expiry_date:d.expiry_date||'',license_status:d.license_status}); }); });
    document.querySelectorAll('[data-edit-violation]').forEach(function (button) { button.addEventListener('click', function () { var d=JSON.parse(button.dataset.editViolation); openEdit('violationDialog','update_violation','تعديل المخالفة','حفظ التعديلات',d,{violation_number:d.violation_number,violation_type:d.violation_type,boat_id:d.boat_id||'',boat_owner_name:d.boat_owner_name||'',violation_date:(d.violation_date||'').replace(' ','T').slice(0,16),fine_amount:d.fine_amount,violation_status:d.violation_status,violation_description:d.violation_description||''}); }); });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
