<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'region_manager', 'gov_supervisor', 'port_supervisor']);
$pageTitle = 'تفاصيل المرفأ';
$activeRoute = 'harbor_details.php';
$hidePageHeading = true;
$bodyClass = 'harbor-details-page';
$pdo = db();
$role = $currentUserData['role_code'];

function harborRedirect(int $portId): string
{
    return BASE_URL . '/dashboard/harbor_details.php?port_id=' . $portId;
}

function harborPercent(int $occupied, int $capacity): float
{
    return $capacity > 0 ? min(100, round(($occupied / $capacity) * 100, 1)) : 0;
}

function occupancyTone(float $percent): string
{
    if ($percent >= 95) return 'critical';
    if ($percent >= 80) return 'danger';
    if ($percent >= 50) return 'warning';
    return 'success';
}

function protectedIdentity(string $identity): string
{
    $identity = trim($identity);
    return $identity === '' ? '' : password_hash($identity, PASSWORD_DEFAULT);
}

/**
 * Store harbor documents outside the public web root. Files are served below
 * only after the user's harbor scope has been checked again.
 *
 * @return array{path:string, full_path:string}|null
 */
function storeHarborAttachment(string $field, string $category): ?array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new InvalidArgumentException('تعذر رفع المرفق. أعد اختيار الملف ثم حاول مرة أخرى.');
    }
    if ((int)($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new InvalidArgumentException('حجم المرفق يجب ألا يتجاوز 10 ميجابايت.');
    }

    $mimeTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($mimeTypes[$mime])) {
        throw new InvalidArgumentException('نوع المرفق غير مدعوم. استخدم PDF أو JPG أو PNG أو WEBP.');
    }

    $directory = BASE_PATH . '/storage/harbor_uploads/' . $category;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('تعذر إنشاء مجلد مرفقات المرفأ.');
    }
    $filename = bin2hex(random_bytes(20)) . '.' . $mimeTypes[$mime];
    $fullPath = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('تعذر حفظ المرفق المرفوع.');
    }

    return ['path' => $category . '/' . $filename, 'full_path' => $fullPath];
}

function harborAttachmentFullPath(string $relativePath): ?string
{
    $root = realpath(BASE_PATH . '/storage/harbor_uploads');
    $file = realpath(BASE_PATH . '/storage/harbor_uploads/' . ltrim(str_replace('\\', '/', $relativePath), '/'));
    if (!$root || !$file || !is_file($file)) return null;
    $rootPrefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
    $normalizedFile = str_replace('\\', '/', $file);
    return str_starts_with($normalizedFile, $rootPrefix) ? $file : null;
}

$portsSql = "SELECT p.*, g.name AS governorate_name, g.region_id, r.name AS region_name
             FROM ports p
             JOIN governorates g ON g.id = p.governorate_id
             JOIN regions r ON r.id = g.region_id
             WHERE 1=1";
$portsParams = [];
if ($role === 'region_manager') {
    $portsSql .= ' AND g.region_id = ?';
    $portsParams[] = (int)$currentUserData['region_id'];
} elseif ($role === 'gov_supervisor') {
    $portsSql .= ' AND p.governorate_id = ?';
    $portsParams[] = (int)$currentUserData['governorate_id'];
} elseif ($role === 'port_supervisor') {
    $portsSql .= ' AND p.id = ?';
    $portsParams[] = (int)$currentUserData['port_id'];
}
$portsSql .= ' ORDER BY r.name, g.name, p.name';
$stmt = $pdo->prepare($portsSql);
$stmt->execute($portsParams);
$portsList = $stmt->fetchAll();
$allowedPortIds = array_map('intval', array_column($portsList, 'id'));
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$requestedPortId = (int)($isPost ? ($_POST['port_id'] ?? 0) : ($_GET['port_id'] ?? 0));
if ($isPost && !in_array($requestedPortId, $allowedPortIds, true)) {
    http_response_code(403);
    exit('غير مصرح لك بتعديل هذا المرفأ.');
}
$portId = in_array($requestedPortId, $allowedPortIds, true) ? $requestedPortId : (int)($allowedPortIds[0] ?? 0);
$redirectUrl = harborRedirect($portId);

if ($portId && isset($_GET['attachment'], $_GET['attachment_id'])) {
    $attachmentType = $_GET['attachment'];
    $attachmentId = (int)$_GET['attachment_id'];
    $attachmentSources = [
        'license' => ['table' => 'harbor_licenses', 'category' => 'licenses'],
        'violation' => ['table' => 'harbor_violations', 'category' => 'violations'],
    ];
    if (!isset($attachmentSources[$attachmentType]) || $attachmentId < 1) {
        http_response_code(404);
        exit('المرفق غير موجود.');
    }
    $source = $attachmentSources[$attachmentType];
    $attachmentStmt = $pdo->prepare("SELECT attachment_path FROM {$source['table']} WHERE id = ? AND port_id = ?");
    $attachmentStmt->execute([$attachmentId, $portId]);
    $attachmentPath = (string)$attachmentStmt->fetchColumn();
    if ($attachmentPath === '' || !str_starts_with($attachmentPath, $source['category'] . '/')) {
        http_response_code(404);
        exit('المرفق غير موجود.');
    }
    $attachmentFile = harborAttachmentFullPath($attachmentPath);
    if (!$attachmentFile) {
        http_response_code(404);
        exit('المرفق غير موجود.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($attachmentFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($attachmentFile));
    header('Content-Disposition: attachment; filename="harbor-attachment-' . $attachmentId . '.' . pathinfo($attachmentFile, PATHINFO_EXTENSION) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($attachmentFile);
    exit;
}

if ($isPost && $portId) {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $pendingUpload = null;
    try {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'update_port':
                $name = trim($_POST['name'] ?? '');
                if ($name === '') throw new InvalidArgumentException('اسم المرفأ مطلوب.');
                $locationName = trim($_POST['location_name'] ?? '') ?: null;
                $locationUrl = trim($_POST['location_url'] ?? '') ?: null;
                if ($locationUrl && !filter_var($locationUrl, FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException('رابط الموقع غير صحيح.');
                }
                $pdo->prepare('UPDATE ports SET name = ?, location_name = ?, location_url = ? WHERE id = ?')
                    ->execute([$name, $locationName, $locationUrl, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تم تحديث بيانات المرفأ.');
                break;

            case 'update_capacities':
                foreach (['large', 'small', 'recreational'] as $type) {
                    $capacity = max(0, (int)($_POST['capacity_' . $type] ?? 0));
                    $status = ($_POST['status_' . $type] ?? 'available') === 'stopped' ? 'stopped' : 'available';
                    $pdo->prepare(
                        "INSERT INTO harbor_boat_capacities (port_id, boat_type, capacity, status)
                         VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), status = VALUES(status)"
                    )->execute([$portId, $type, $capacity, $status]);
                }
                redirectWithMessage($redirectUrl, 'success', 'تم تحديث قدرات القوارب.');
                break;

            case 'add_boat':
                $name = trim($_POST['boat_name'] ?? '');
                $registration = trim($_POST['registration_no'] ?? '') ?: null;
                $type = $_POST['boat_type'] ?? 'small';
                $status = $_POST['harbor_status'] ?? 'occupied';
                if ($name === '' || !in_array($type, ['large','small','recreational'], true)
                    || !in_array($status, ['occupied','disabled','inactive'], true)) {
                    throw new InvalidArgumentException('بيانات القارب غير مكتملة.');
                }
                $pdo->prepare('INSERT INTO boats (name, registration_no, boat_type, harbor_status, home_port_id) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$name, $registration, $type, $status, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تمت إضافة القارب وربطه بالمرفأ.');
                break;

            case 'update_boat':
                $boatId = (int)($_POST['boat_id'] ?? 0);
                $name = trim($_POST['boat_name'] ?? '');
                $registration = trim($_POST['registration_no'] ?? '') ?: null;
                $type = $_POST['boat_type'] ?? '';
                $status = $_POST['harbor_status'] ?? '';
                if ($boatId < 1 || $name === ''
                    || !in_array($type, ['large','small','recreational','unclassified'], true)
                    || !in_array($status, ['occupied','disabled','inactive','unclassified'], true)) {
                    throw new InvalidArgumentException('بيانات القارب غير مكتملة.');
                }
                $boatCheck = $pdo->prepare('SELECT COUNT(*) FROM boats WHERE id = ? AND home_port_id = ?');
                $boatCheck->execute([$boatId, $portId]);
                if (!$boatCheck->fetchColumn()) throw new InvalidArgumentException('القارب المحدد غير مرتبط بهذا المرفأ.');
                $pdo->prepare('UPDATE boats SET name = ?, registration_no = ?, boat_type = ?, harbor_status = ? WHERE id = ? AND home_port_id = ?')
                    ->execute([$name, $registration, $type, $status, $boatId, $portId]);
                redirectWithMessage($redirectUrl, 'success', 'تم تحديث بيانات القارب.');
                break;

            case 'add_worker':
                $name = trim($_POST['employee_name'] ?? '');
                $type = $_POST['worker_type'] ?? '';
                if ($name === '' || !in_array($type, ['supervisor','contractor','fisherman','foreign_worker'], true)) {
                    throw new InvalidArgumentException('اسم العامل وفئته مطلوبان.');
                }
                $pdo->prepare(
                    'INSERT INTO harbor_workers (port_id, employee_name, identity_number, nationality, worker_type, mobile_number, employment_status, start_date, end_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $portId, $name, protectedIdentity($_POST['identity_number'] ?? '') ?: null,
                    ($_POST['nationality'] ?? '') === 'non_saudi' ? 'non_saudi' : 'saudi', $type,
                    trim($_POST['mobile_number'] ?? '') ?: null,
                    in_array($_POST['employment_status'] ?? '', ['active','suspended','expired'], true) ? $_POST['employment_status'] : 'active',
                    $_POST['start_date'] ?: null, $_POST['end_date'] ?: null,
                ]);
                redirectWithMessage($redirectUrl, 'success', 'تمت إضافة سجل القوى البشرية.');
                break;

            case 'add_license':
                $number = trim($_POST['license_number'] ?? '');
                $holder = trim($_POST['license_holder_name'] ?? '');
                if ($number === '' || $holder === '') throw new InvalidArgumentException('رقم الرخصة واسم صاحبها مطلوبان.');
                $pendingUpload = storeHarborAttachment('license_attachment', 'licenses');
                $pdo->prepare(
                    'INSERT INTO harbor_licenses (port_id, license_number, license_type, license_holder_name, boat_number, issue_date, expiry_date, license_status, attachment_path)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $portId, $number, ($_POST['license_type'] ?? '') === 'operational' ? 'operational' : 'seasonal', $holder,
                    trim($_POST['boat_number'] ?? '') ?: null, $_POST['issue_date'] ?: null, $_POST['expiry_date'] ?: null,
                    in_array($_POST['license_status'] ?? '', ['valid','expired','suspended','cancelled'], true) ? $_POST['license_status'] : 'valid',
                    $pendingUpload['path'] ?? null,
                ]);
                redirectWithMessage($redirectUrl, 'success', 'تم تسجيل الرخصة.');
                break;

            case 'add_violation':
                $number = trim($_POST['violation_number'] ?? '');
                $type = trim($_POST['violation_type'] ?? '');
                if ($number === '' || $type === '') throw new InvalidArgumentException('رقم المخالفة ونوعها مطلوبان.');
                $boatId = (int)($_POST['boat_id'] ?? 0) ?: null;
                if ($boatId) {
                    $check = $pdo->prepare('SELECT COUNT(*) FROM boats WHERE id = ? AND home_port_id = ?');
                    $check->execute([$boatId, $portId]);
                    if (!$check->fetchColumn()) throw new InvalidArgumentException('القارب المحدد غير مرتبط بهذا المرفأ.');
                }
                $pendingUpload = storeHarborAttachment('violation_attachment', 'violations');
                $pdo->prepare(
                    'INSERT INTO harbor_violations (port_id, violation_number, violation_type, violation_description, violation_date, boat_id, boat_owner_name, fine_amount, violation_status, created_by, attachment_path)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $portId, $number, $type, trim($_POST['violation_description'] ?? '') ?: null,
                    ($_POST['violation_date'] ?? '') ?: date('Y-m-d H:i:s'), $boatId,
                    trim($_POST['boat_owner_name'] ?? '') ?: null, max(0, (float)($_POST['fine_amount'] ?? 0)),
                    in_array($_POST['violation_status'] ?? '', ['open','paid','appealed','closed'], true) ? $_POST['violation_status'] : 'open',
                    (int)$currentUserData['id'],
                    $pendingUpload['path'] ?? null,
                ]);
                redirectWithMessage($redirectUrl, 'success', 'تم تسجيل المخالفة.');
                break;

            default:
                throw new InvalidArgumentException('الإجراء المطلوب غير معروف.');
        }
    } catch (Throwable $e) {
        if ($pendingUpload && is_file($pendingUpload['full_path'])) unlink($pendingUpload['full_path']);
        error_log('Harbor details action: ' . $e->getMessage());
        $message = $e instanceof InvalidArgumentException ? $e->getMessage() : 'تعذر حفظ البيانات. تحقق من عدم تكرار الرقم ثم أعد المحاولة.';
        redirectWithMessage($redirectUrl, 'error', $message);
    }
}

$harbor = null;
$boatTypes = [
    'large' => ['label' => 'كبير', 'capacity' => 0, 'occupied' => 0, 'disabled' => 0, 'status' => 'available'],
    'small' => ['label' => 'صغير', 'capacity' => 0, 'occupied' => 0, 'disabled' => 0, 'status' => 'available'],
    'recreational' => ['label' => 'نزهة', 'capacity' => 0, 'occupied' => 0, 'disabled' => 0, 'status' => 'available'],
];
$boats = $workers = $licenses = $violations = [];

if ($portId) {
    foreach ($portsList as $item) if ((int)$item['id'] === $portId) { $harbor = $item; break; }

    $stmt = $pdo->prepare('SELECT * FROM harbor_boat_capacities WHERE port_id = ?');
    $stmt->execute([$portId]);
    foreach ($stmt->fetchAll() as $row) {
        $boatTypes[$row['boat_type']]['capacity'] = (int)$row['capacity'];
        $boatTypes[$row['boat_type']]['status'] = $row['status'];
    }

    $stmt = $pdo->prepare('SELECT * FROM boats WHERE home_port_id = ? ORDER BY boat_type, name');
    $stmt->execute([$portId]);
    $boats = $stmt->fetchAll();
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

    $stmt = $pdo->prepare('SELECT * FROM harbor_workers WHERE port_id = ? ORDER BY worker_type, employee_name');
    $stmt->execute([$portId]);
    $workers = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT * FROM harbor_licenses WHERE port_id = ? ORDER BY created_at DESC');
    $stmt->execute([$portId]);
    $licenses = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT v.*, b.name AS boat_name FROM harbor_violations v LEFT JOIN boats b ON b.id = v.boat_id WHERE v.port_id = ? ORDER BY v.violation_date DESC');
    $stmt->execute([$portId]);
    $violations = $stmt->fetchAll();
}

$totalCapacity = array_sum(array_column($boatTypes, 'capacity'));
$totalOccupied = array_sum(array_column($boatTypes, 'occupied'));
$totalDisabled = array_sum(array_column($boatTypes, 'disabled'));
$totalAvailable = max(0, $totalCapacity - $totalOccupied);
$occupancyRate = harborPercent($totalOccupied, $totalCapacity);
$occupancyTone = occupancyTone($occupancyRate);
$workerCounts = ['supervisor' => 0, 'contractor' => 0, 'fisherman' => 0, 'foreign_worker' => 0];
foreach ($workers as $worker) $workerCounts[$worker['worker_type']]++;
$seasonalLicenses = count(array_filter($licenses, fn($license) => $license['license_type'] === 'seasonal'));

if ($portId && ($_GET['export'] ?? '') === 'csv') {
    $safeName = preg_replace('/[^\p{L}\p{N}_-]+/u', '-', $harbor['name']);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="harbor-' . rawurlencode($safeName) . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['تقرير المرفأ', $harbor['name']]);
    fputcsv($out, ['المنطقة', $harbor['region_name'], 'المحافظة', $harbor['governorate_name']]);
    fputcsv($out, ['إجمالي الاستيعاب', $totalCapacity, 'الشاغلة', $totalOccupied, 'المعطلة', $totalDisabled, 'نسبة التشغيل', $occupancyRate . '%']);
    fputcsv($out, []);
    fputcsv($out, ['نوع القارب', 'الاستيعاب', 'الشاغلة', 'المعطلة', 'المتاحة', 'نسبة التشغيل']);
    foreach ($boatTypes as $type) fputcsv($out, [$type['label'], $type['capacity'], $type['occupied'], $type['disabled'], $type['available'], $type['percent'] . '%']);
    fputcsv($out, []);
    fputcsv($out, ['فئة القوى البشرية', 'إجمالي السجلات']);
    foreach (['supervisor'=>'المشرفون','contractor'=>'المتعاقدون','fisherman'=>'الصيادون','foreign_worker'=>'الأجانب'] as $key=>$label) fputcsv($out, [$label, $workerCounts[$key]]);
    fputcsv($out, []);
    fputcsv($out, ['الرخص الموسمية', $seasonalLicenses, 'المخالفات', count($violations)]);
    fclose($out);
    exit;
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="harbor-report">
<?php if (!$portId || !$harbor): ?>
    <div class="panel"><div class="placeholder-box">لا يوجد مرفأ متاح ضمن نطاق صلاحيتك.</div></div>
<?php else: ?>
    <div class="harbor-breadcrumb"><a href="<?= BASE_URL ?>/dashboard/port.php?port_id=<?= $portId ?>">إدارة المرافق</a><span>/</span><b>تفاصيل المرفأ</b></div>
    <header class="harbor-hero">
        <div><span class="eyebrow">بطاقة تشغيل المرفأ</span><h1>تفاصيل المرفأ</h1><p>مرجع تشغيلي حي للقوارب والقوى البشرية والتراخيص والمخالفات</p></div>
        <?php if (count($portsList) > 1): ?>
        <form method="get" class="harbor-switcher">
            <label for="harborSelect">المرفأ المعروض</label>
            <select id="harborSelect" name="port_id" onchange="this.form.submit()">
                <?php foreach ($portsList as $port): ?><option value="<?= (int)$port['id'] ?>" <?= (int)$port['id'] === $portId ? 'selected' : '' ?>><?= e($port['name']) ?> — <?= e($port['governorate_name']) ?></option><?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </header>

    <section class="harbor-section identity-section">
        <div class="section-heading"><span>1</span><h2>البيانات الأساسية</h2></div>
        <div class="identity-grid">
            <div class="metric-card tone-purple"><i class="card-icon">⌖</i><small>المنطقة</small><strong><?= e($harbor['region_name']) ?></strong></div>
            <div class="metric-card tone-green"><i class="card-icon">⌂</i><small>المحافظة</small><strong><?= e($harbor['governorate_name']) ?></strong></div>
            <div class="metric-card tone-blue wide"><i class="card-icon">⚓</i><small>اسم المرفأ</small><strong><?= e($harbor['name']) ?></strong></div>
            <?php if ($harbor['location_url']): ?><a class="metric-card tone-green" href="<?= e($harbor['location_url']) ?>" target="_blank" rel="noopener"><i class="card-icon">⌖</i><small>الموقع</small><strong><?= e($harbor['location_name'] ?: 'فتح الخريطة') ?></strong></a><?php else: ?><div class="metric-card tone-green"><i class="card-icon">⌖</i><small>الموقع</small><strong><?= e($harbor['location_name'] ?: 'غير مسجل') ?></strong></div><?php endif; ?>
            <div class="metric-card tone-red compact"><i class="card-icon">◴</i><small>نسبة التشغيل</small><strong><?= numberAr($occupancyRate, $occupancyRate == (int)$occupancyRate ? 0 : 1) ?>%</strong><span class="mini-progress"><b style="width:<?= $occupancyRate ?>%"></b></span></div>
            <div class="metric-card tone-red compact"><i class="card-icon">⛴</i><small>القوة الاستيعابية</small><strong><?= numberAr($totalCapacity) ?> <em>قارب</em></strong></div>
        </div>
    </section>

    <section class="harbor-section">
        <div class="section-heading"><span>2</span><h2>القوارب حسب النوع</h2></div>
        <div class="boat-type-grid">
        <?php $boatIcons = ['large'=>'⛴','small'=>'⚓','recreational'=>'◢']; foreach ($boatTypes as $key => $type): ?>
            <button class="boat-type-card clickable" data-detail="boatsDialog" data-filter="<?= $key ?>">
                <div class="boat-card-head"><i><?= $boatIcons[$key] ?></i><div><h3><?= $type['label'] ?></h3><p>القدرة الاستيعابية: <b><?= numberAr($type['capacity']) ?></b> قارب</p></div></div>
                <div class="boat-stat-row"><span><small>المتاحة</small><b><?= numberAr($type['available']) ?></b></span><span class="is-danger"><small>المعطلة</small><b><?= numberAr($type['disabled']) ?></b></span><span class="is-success"><small>الشاغلة</small><b><?= numberAr($type['occupied']) ?></b></span></div>
                <div class="occupancy-line"><span>نسبة التشغيل <b><?= numberAr($type['percent'], $type['percent'] == (int)$type['percent'] ? 0 : 1) ?>%</b></span><i class="ring <?= occupancyTone($type['percent']) ?>" style="--value:<?= $type['percent'] ?>"></i></div>
            </button>
        <?php endforeach; ?>
        </div>
        <div class="formula-strip"><span>▦</span>إجمالي الاستيعاب = <?= implode(' + ', array_map(fn($type) => numberAr($type['capacity']), $boatTypes)) ?> = <b><?= numberAr($totalCapacity) ?> قاربًا</b></div>
    </section>

    <div class="harbor-two-column">
        <section class="harbor-section">
            <div class="section-heading"><span>3</span><h2>القوى البشرية</h2></div>
            <div class="people-grid">
                <?php foreach ([['supervisor','المشرفون','●','purple'],['contractor','المتعاقدون','♟','green'],['fisherman','الصيادون','◆','blue'],['foreign_worker','الأجانب','●','orange']] as [$key,$label,$icon,$tone]): ?>
                <button class="person-card clickable tone-<?= $tone ?>" data-detail="workersDialog" data-filter="<?= $key ?>"><i><?= $icon ?></i><span><?= $label ?></span><b><?= numberAr($workerCounts[$key]) ?></b></button>
                <?php endforeach; ?>
            </div>
            <button class="info-strip clickable" data-detail="workersDialog"><span>♟</span>البيانات التفصيلية: الاسم، الهوية/الإقامة، الجنسية، الفئة، الجوال، الحالة وتواريخ العمل.</button>
        </section>

        <section class="harbor-section">
            <div class="section-heading"><span>4</span><h2>التراخيص والمخالفات</h2></div>
            <div class="license-grid">
                <button class="license-summary tone-blue clickable" data-detail="licensesDialog" data-filter="seasonal"><span class="summary-top"><i>▣</i><span><small>الرخص الموسمية</small><b><?= numberAr($seasonalLicenses) ?></b></span></span><em>رقم الرخصة · النوع · الإصدار · الانتهاء · الحالة</em></button>
                <button class="license-summary tone-red clickable" data-detail="violationsDialog"><span class="summary-top"><i>!</i><span><small>المخالفات</small><b><?= numberAr(count($violations)) ?></b></span></span><em>رقم المخالفة · النوع · القارب · الغرامة · الحالة</em></button>
            </div>
        </section>
    </div>

    <section class="harbor-section calculation-section">
        <div class="section-heading"><span>5</span><h2>العمليات الحسابية</h2></div>
        <div class="calculation-grid">
            <button class="calc-card clickable" data-detail="boatsDialog"><small>إجمالي القدرة الاستيعابية</small><b><?= numberAr($totalCapacity) ?></b><span>قاربًا</span></button>
            <button class="calc-card tone-green clickable" data-detail="boatsDialog"><small>إجمالي القوارب الشاغلة</small><b><?= numberAr($totalOccupied) ?></b><span>قارب</span></button>
            <button class="calc-card tone-red clickable" data-detail="boatsDialog"><small>إجمالي المعطلة</small><b><?= numberAr($totalDisabled) ?></b><span>قارب</span></button>
            <button class="calc-card clickable" data-detail="boatsDialog"><small>القوارب المتاحة</small><b><?= numberAr($totalAvailable) ?></b><span>قاربًا</span></button>
            <button class="calc-card tone-purple clickable" data-detail="boatsDialog"><small>نسبة تشغيل المرفأ</small><b><?= numberAr($occupancyRate, $occupancyRate == (int)$occupancyRate ? 0 : 1) ?>%</b><span><?= numberAr($totalOccupied) ?> ÷ <?= numberAr($totalCapacity) ?></span></button>
        </div>
    </section>

    <section class="harbor-section actions-section">
        <div class="section-heading"><span>6</span><h2>الإجراءات</h2></div>
        <div class="harbor-actions">
            <button class="harbor-action tone-purple" data-dialog="editHarborDialog"><i>✎</i><span>تعديل بيانات المرفأ</span></button>
            <button class="harbor-action tone-blue" data-dialog="boatsManageDialog"><i>⛴</i><span>إدارة القوارب</span></button>
            <button class="harbor-action tone-green" data-dialog="workerAddDialog"><i>♟</i><span>إدارة القوى البشرية</span></button>
            <button class="harbor-action tone-blue" data-dialog="licenseAddDialog"><i>✚</i><span>إضافة رخصة</span></button>
            <button class="harbor-action tone-red" data-dialog="violationAddDialog"><i>!</i><span>إضافة مخالفة</span></button>
            <button class="harbor-action tone-purple" data-detail="boatsDialog"><i>▤</i><span>عرض التفاصيل</span></button>
            <button class="harbor-action" onclick="window.print()"><i>▣</i><span>طباعة</span></button>
            <a class="harbor-action tone-green" href="?port_id=<?= $portId ?>&amp;export=csv"><i>▥</i><span>تصدير Excel</span></a>
            <a class="harbor-action tone-orange" href="?port_id=<?= $portId ?>"><i>↻</i><span>تحديث البيانات</span></a>
        </div>
    </section>

    <section class="harbor-note"><div class="section-heading"><span>7</span><h2>ملاحظات التشغيل</h2></div><div class="note-grid"><p>واجهة عربية كاملة من اليمين إلى اليسار.</p><p>كل بطاقة رقمية تفتح السجلات التي كوّنت قيمتها.</p><p>الأرقام محسوبة من البيانات المرتبطة وليست مدخلة يدويًا.</p><p><b class="dot success"></b> متاح <b class="dot warning"></b> تنبيه <b class="dot danger"></b> متوقف</p></div></section>

    <?php
    $typeLabels = ['large'=>'كبير','small'=>'صغير','recreational'=>'نزهة','unclassified'=>'غير مصنف'];
    $boatStatusLabels = ['occupied'=>'شاغل','disabled'=>'معطل','inactive'=>'غير نشط','unclassified'=>'غير مصنف'];
    $workerTypeLabels = ['supervisor'=>'مشرف','contractor'=>'متعاقد','fisherman'=>'صياد','foreign_worker'=>'أجنبي'];
    $employmentLabels = ['active'=>'نشط','suspended'=>'موقوف','expired'=>'منتهي'];
    $licenseStatusLabels = ['valid'=>'سارية','expired'=>'منتهية','suspended'=>'معلقة','cancelled'=>'ملغاة'];
    $violationStatusLabels = ['open'=>'مفتوحة','paid'=>'مسددة','appealed'=>'معترض عليها','closed'=>'مغلقة'];
    ?>

    <dialog id="harborInfoDialog" class="harbor-dialog"><div class="dialog-head"><div><small>البيانات الأساسية</small><h3><?= e($harbor['name']) ?></h3></div><button data-close aria-label="إغلاق">×</button></div><div class="detail-list"><p><span>المنطقة</span><b><?= e($harbor['region_name']) ?></b></p><p><span>المحافظة</span><b><?= e($harbor['governorate_name']) ?></b></p><p><span>الموقع</span><b><?= e($harbor['location_name'] ?: 'غير مسجل') ?></b></p><p><span>الحالة</span><b><?= $harbor['is_active'] ? 'نشط' : 'متوقف' ?></b></p></div></dialog>

    <dialog id="boatsDialog" class="harbor-dialog harbor-dialog-wide"><div class="dialog-head"><div><small>السجلات المرتبطة</small><h3>قوارب المرفأ</h3></div><button data-close aria-label="إغلاق">×</button></div><div class="table-responsive"><table><thead><tr><th>القارب</th><th>رقم التسجيل</th><th>النوع</th><th>الحالة</th></tr></thead><tbody><?php if (!$boats): ?><tr><td colspan="4">لا توجد قوارب مرتبطة.</td></tr><?php else: foreach ($boats as $boat): ?><tr data-row-type="<?= e($boat['boat_type']) ?>"><td><?= e($boat['name']) ?></td><td><?= e($boat['registration_no'] ?: '—') ?></td><td><?= $typeLabels[$boat['boat_type']] ?></td><td><span class="record-status status-<?= e($boat['harbor_status']) ?>"><?= $boatStatusLabels[$boat['harbor_status']] ?></span></td></tr><?php endforeach; endif; ?></tbody></table></div></dialog>

    <dialog id="workersDialog" class="harbor-dialog harbor-dialog-wide"><div class="dialog-head"><div><small>السجلات المرتبطة</small><h3>القوى البشرية</h3></div><button data-close aria-label="إغلاق">×</button></div><div class="table-responsive"><table><thead><tr><th>الاسم</th><th>الهوية/الإقامة</th><th>الفئة</th><th>الجنسية</th><th>الجوال</th><th>الحالة</th><th>البداية</th><th>الانتهاء</th></tr></thead><tbody><?php if (!$workers): ?><tr><td colspan="8">لا توجد سجلات قوى بشرية.</td></tr><?php else: foreach ($workers as $worker): ?><tr data-row-type="<?= e($worker['worker_type']) ?>"><td><?= e($worker['employee_name']) ?></td><td dir="ltr"><?= e($worker['identity_number'] ?: '—') ?></td><td><?= $workerTypeLabels[$worker['worker_type']] ?></td><td><?= $worker['nationality'] === 'saudi' ? 'سعودي' : 'غير سعودي' ?></td><td><?= e($worker['mobile_number'] ?: '—') ?></td><td><?= $employmentLabels[$worker['employment_status']] ?></td><td><?= e($worker['start_date'] ?: '—') ?></td><td><?= e($worker['end_date'] ?: '—') ?></td></tr><?php endforeach; endif; ?></tbody></table></div></dialog>

    <dialog id="licensesDialog" class="harbor-dialog harbor-dialog-wide"><div class="dialog-head"><div><small>السجلات المرتبطة</small><h3>التراخيص</h3></div><button data-close aria-label="إغلاق">×</button></div><div class="table-responsive"><table><thead><tr><th>رقم الرخصة</th><th>النوع</th><th>صاحب الرخصة</th><th>الانتهاء</th><th>الحالة</th><th>المرفق</th></tr></thead><tbody><?php if (!$licenses): ?><tr><td colspan="6">لا توجد تراخيص مسجلة.</td></tr><?php else: foreach ($licenses as $license): ?><tr data-row-type="<?= e($license['license_type']) ?>"><td><?= e($license['license_number']) ?></td><td><?= $license['license_type'] === 'seasonal' ? 'موسمية' : 'تشغيلية' ?></td><td><?= e($license['license_holder_name']) ?></td><td><?= e($license['expiry_date'] ?: '—') ?></td><td><?= $licenseStatusLabels[$license['license_status']] ?></td><td><?php if ($license['attachment_path']): ?><a href="?port_id=<?= $portId ?>&amp;attachment=license&amp;attachment_id=<?= (int)$license['id'] ?>">تنزيل</a><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></dialog>

    <dialog id="violationsDialog" class="harbor-dialog harbor-dialog-wide"><div class="dialog-head"><div><small>السجلات المرتبطة</small><h3>المخالفات</h3></div><button data-close aria-label="إغلاق">×</button></div><div class="table-responsive"><table><thead><tr><th>رقم المخالفة</th><th>النوع</th><th>القارب</th><th>الغرامة</th><th>الحالة</th><th>المرفق</th></tr></thead><tbody><?php if (!$violations): ?><tr><td colspan="6">لا توجد مخالفات مسجلة.</td></tr><?php else: foreach ($violations as $violation): ?><tr><td><?= e($violation['violation_number']) ?></td><td><?= e($violation['violation_type']) ?></td><td><?= e($violation['boat_name'] ?: '—') ?></td><td><?= numberAr($violation['fine_amount'], 2) ?></td><td><?= $violationStatusLabels[$violation['violation_status']] ?></td><td><?php if ($violation['attachment_path']): ?><a href="?port_id=<?= $portId ?>&amp;attachment=violation&amp;attachment_id=<?= (int)$violation['id'] ?>">تنزيل</a><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></dialog>

    <dialog id="editHarborDialog" class="harbor-dialog"><form method="post"><div class="dialog-head"><div><small>تحرير</small><h3>بيانات المرفأ</h3></div><button type="button" data-close>×</button></div><?= csrfField() ?><input type="hidden" name="action" value="update_port"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="form-grid"><label class="span-2">اسم المرفأ<input name="name" value="<?= e($harbor['name']) ?>" required></label><label>المنطقة<input value="<?= e($harbor['region_name']) ?>" disabled></label><label>المحافظة<input value="<?= e($harbor['governorate_name']) ?>" disabled></label><label>اسم الموقع<input name="location_name" value="<?= e($harbor['location_name']) ?>" placeholder="مثال: الواجهة البحرية"></label><label>رابط Google Maps<input type="url" name="location_url" value="<?= e($harbor['location_url']) ?>" placeholder="https://maps.google.com/..."></label></div><button class="btn btn-primary dialog-submit">حفظ التعديلات</button></form></dialog>

    <dialog id="boatsManageDialog" class="harbor-dialog harbor-dialog-wide">
        <div class="dialog-head"><div><small>إدارة القوارب</small><h3>القدرات والقوارب المسجلة</h3></div><button data-close>×</button></div>
        <form method="post" class="capacity-form"><?= csrfField() ?><input type="hidden" name="action" value="update_capacities"><input type="hidden" name="port_id" value="<?= $portId ?>"><?php foreach ($boatTypes as $key=>$type): ?><label><?= $type['label'] ?><input type="number" min="0" name="capacity_<?= $key ?>" value="<?= $type['capacity'] ?>"><select name="status_<?= $key ?>"><option value="available" <?= $type['status'] !== 'stopped' ? 'selected' : '' ?>>متاح</option><option value="stopped" <?= $type['status'] === 'stopped' ? 'selected' : '' ?>>متوقف</option></select></label><?php endforeach; ?><button class="btn btn-outline">حفظ القدرات</button></form>
        <hr>
        <?php if ($boats): ?>
        <div class="boat-edit-list">
            <h4>تعديل القوارب الحالية</h4>
            <?php foreach ($boats as $boat): ?>
            <form method="post" class="boat-edit-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_boat">
                <input type="hidden" name="port_id" value="<?= $portId ?>">
                <input type="hidden" name="boat_id" value="<?= (int)$boat['id'] ?>">
                <label>اسم القارب<input name="boat_name" value="<?= e($boat['name']) ?>" required></label>
                <label>رقم التسجيل<input name="registration_no" value="<?= e($boat['registration_no']) ?>"></label>
                <label>النوع<select name="boat_type"><?php foreach ($typeLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $boat['boat_type'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
                <label>الحالة<select name="harbor_status"><?php foreach ($boatStatusLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $boat['harbor_status'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
                <button class="btn btn-outline">حفظ</button>
            </form>
            <?php endforeach; ?>
        </div>
        <hr>
        <?php endif; ?>
        <form method="post">
            <?= csrfField() ?><input type="hidden" name="action" value="add_boat"><input type="hidden" name="port_id" value="<?= $portId ?>">
            <h4>إضافة قارب</h4>
            <div class="form-grid"><label>اسم القارب<input name="boat_name" required></label><label>رقم التسجيل<input name="registration_no"></label><label>النوع<select name="boat_type"><option value="large">كبير</option><option value="small">صغير</option><option value="recreational">نزهة</option></select></label><label>الحالة<select name="harbor_status"><option value="occupied">شاغل</option><option value="disabled">معطل</option><option value="inactive">غير نشط</option></select></label></div>
            <button class="btn btn-primary dialog-submit">إضافة القارب</button>
        </form>
    </dialog>

    <dialog id="workerAddDialog" class="harbor-dialog"><form method="post"><div class="dialog-head"><div><small>إدارة القوى البشرية</small><h3>إضافة سجل جديد</h3></div><button type="button" data-close>×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_worker"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="form-grid"><label class="span-2">الاسم<input name="employee_name" required></label><label>الهوية أو الإقامة<input name="identity_number" autocomplete="off"></label><label>رقم الجوال<input name="mobile_number"></label><label>الجنسية<select name="nationality"><option value="saudi">سعودي</option><option value="non_saudi">غير سعودي</option></select></label><label>الفئة<select name="worker_type"><option value="supervisor">مشرف</option><option value="contractor">متعاقد</option><option value="fisherman">صياد</option><option value="foreign_worker">أجنبي</option></select></label><label>الحالة<select name="employment_status"><option value="active">نشط</option><option value="suspended">موقوف</option><option value="expired">منتهي</option></select></label><label>تاريخ البداية<input type="date" name="start_date"></label><label>تاريخ الانتهاء<input type="date" name="end_date"></label></div><button class="btn btn-primary dialog-submit">إضافة السجل</button></form></dialog>

    <dialog id="licenseAddDialog" class="harbor-dialog"><form method="post" enctype="multipart/form-data"><div class="dialog-head"><div><small>التراخيص</small><h3>إضافة رخصة</h3></div><button type="button" data-close>×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_license"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="form-grid"><label>رقم الرخصة<input name="license_number" required></label><label>النوع<select name="license_type"><option value="seasonal">موسمية</option><option value="operational">تشغيلية</option></select></label><label class="span-2">اسم صاحب الرخصة<input name="license_holder_name" required></label><label>رقم القارب<input name="boat_number"></label><label>الحالة<select name="license_status"><option value="valid">سارية</option><option value="expired">منتهية</option><option value="suspended">معلقة</option><option value="cancelled">ملغاة</option></select></label><label>تاريخ الإصدار<input type="date" name="issue_date"></label><label>تاريخ الانتهاء<input type="date" name="expiry_date"></label><label class="span-2">نسخة الرخصة أو المستندات<input type="file" name="license_attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"><small>PDF أو صورة، بحد أقصى 10 ميجابايت.</small></label></div><button class="btn btn-primary dialog-submit">تسجيل الرخصة</button></form></dialog>

    <dialog id="violationAddDialog" class="harbor-dialog"><form method="post" enctype="multipart/form-data"><div class="dialog-head"><div><small>المخالفات</small><h3>إضافة مخالفة</h3></div><button type="button" data-close>×</button></div><?= csrfField() ?><input type="hidden" name="action" value="add_violation"><input type="hidden" name="port_id" value="<?= $portId ?>"><div class="form-grid"><label>رقم المخالفة<input name="violation_number" required></label><label>نوع المخالفة<input name="violation_type" required></label><label>القارب<select name="boat_id"><option value="">غير محدد</option><?php foreach ($boats as $boat): ?><option value="<?= (int)$boat['id'] ?>"><?= e($boat['name']) ?></option><?php endforeach; ?></select></label><label>صاحب القارب<input name="boat_owner_name"></label><label>تاريخ المخالفة<input type="datetime-local" name="violation_date" value="<?= date('Y-m-d\TH:i') ?>"></label><label>قيمة الغرامة<input type="number" min="0" step="0.01" name="fine_amount"></label><label>الحالة<select name="violation_status"><option value="open">مفتوحة</option><option value="paid">مسددة</option><option value="appealed">معترض عليها</option><option value="closed">مغلقة</option></select></label><label class="span-2">الوصف<textarea name="violation_description" rows="3"></textarea></label><label class="span-2">صورة أو محضر المخالفة<input type="file" name="violation_attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"><small>PDF أو صورة، بحد أقصى 10 ميجابايت.</small></label></div><button class="btn btn-primary dialog-submit">تسجيل المخالفة</button></form></dialog>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
