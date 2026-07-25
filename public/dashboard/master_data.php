<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin']);
$pageTitle   = 'البيانات الأساسية';
$activeRoute = 'master_data.php';

$pdo = db();
$redirectUrl = BASE_URL . '/dashboard/master_data.php';
$section = $_GET['section'] ?? 'regions';

/* ------------------------------------------------------------
   الإجراءات: إضافة أو حذف
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirectWithMessage($redirectUrl . '?section=' . $section, 'error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
    }
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add_region':
                $name = trim($_POST['name'] ?? '');
                if ($name === '') throw new InvalidArgumentException('الاسم مطلوب.');
                $pdo->prepare("INSERT INTO regions (name) VALUES (?)")->execute([$name]);
                redirectWithMessage($redirectUrl . '?section=regions', 'success', 'تمت إضافة المنطقة.');
                break;

            case 'delete_region':
                $pdo->prepare("DELETE FROM regions WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=regions', 'success', 'تم حذف المنطقة.');
                break;

            case 'add_governorate':
                $name = trim($_POST['name'] ?? '');
                $regionId = (int)($_POST['region_id'] ?? 0);
                if ($name === '' || !$regionId) throw new InvalidArgumentException('الاسم والمنطقة مطلوبان.');
                $pdo->prepare("INSERT INTO governorates (region_id, name) VALUES (?, ?)")->execute([$regionId, $name]);
                redirectWithMessage($redirectUrl . '?section=governorates', 'success', 'تمت إضافة المحافظة.');
                break;

            case 'delete_governorate':
                $pdo->prepare("DELETE FROM governorates WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=governorates', 'success', 'تم حذف المحافظة.');
                break;

            case 'add_port':
                $name = trim($_POST['name'] ?? '');
                $govId = (int)($_POST['governorate_id'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                if ($name === '' || !$govId) throw new InvalidArgumentException('الاسم والمحافظة مطلوبان.');
                $pdo->prepare("INSERT INTO ports (governorate_id, name, is_active) VALUES (?, ?, ?)")
                    ->execute([$govId, $name, $isActive]);
                redirectWithMessage($redirectUrl . '?section=ports', 'success', 'تمت إضافة الميناء.');
                break;

            case 'toggle_port':
                $stmt = $pdo->prepare("SELECT is_active FROM ports WHERE id = ?");
                $stmt->execute([(int)$_POST['id']]);
                $current = (int)$stmt->fetchColumn();
                $pdo->prepare("UPDATE ports SET is_active = ? WHERE id = ?")->execute([$current ? 0 : 1, (int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=ports', 'success', 'تم تحديث حالة الميناء.');
                break;

            case 'delete_port':
                $pdo->prepare("DELETE FROM ports WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=ports', 'success', 'تم حذف الميناء.');
                break;

            case 'add_boat':
                $name = trim($_POST['name'] ?? '');
                $reg = trim($_POST['registration_no'] ?? '') ?: null;
                $homePort = (int)($_POST['home_port_id'] ?? 0) ?: null;
                if ($name === '') throw new InvalidArgumentException('اسم القارب مطلوب.');
                $pdo->prepare("INSERT INTO boats (name, registration_no, home_port_id) VALUES (?, ?, ?)")
                    ->execute([$name, $reg, $homePort]);
                redirectWithMessage($redirectUrl . '?section=boats', 'success', 'تمت إضافة القارب.');
                break;

            case 'delete_boat':
                $pdo->prepare("DELETE FROM boats WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=boats', 'success', 'تم حذف القارب.');
                break;

            case 'add_captain':
                $name = trim($_POST['full_name'] ?? '');
                $nid = trim($_POST['national_id'] ?? '') ?: null;
                $phone = trim($_POST['phone'] ?? '') ?: null;
                if ($name === '') throw new InvalidArgumentException('اسم الكابتن مطلوب.');
                $pdo->prepare("INSERT INTO captains (full_name, national_id, phone) VALUES (?, ?, ?)")
                    ->execute([$name, $nid, $phone]);
                redirectWithMessage($redirectUrl . '?section=captains', 'success', 'تمت إضافة الكابتن.');
                break;

            case 'delete_captain':
                $pdo->prepare("DELETE FROM captains WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=captains', 'success', 'تم حذف الكابتن.');
                break;

            case 'add_species':
                $name = trim($_POST['name_ar'] ?? '');
                if ($name === '') throw new InvalidArgumentException('اسم النوع مطلوب.');
                $pdo->prepare("INSERT INTO fish_species (name_ar) VALUES (?)")->execute([$name]);
                redirectWithMessage($redirectUrl . '?section=species', 'success', 'تمت إضافة النوع.');
                break;

            case 'delete_species':
                $pdo->prepare("DELETE FROM fish_species WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=species', 'success', 'تم حذف النوع.');
                break;

            case 'add_trip':
                $tripCode = trim($_POST['trip_code'] ?? '');
                $boatId = (int)($_POST['boat_id'] ?? 0);
                $captainId = (int)($_POST['captain_id'] ?? 0);
                $portId = (int)($_POST['port_id'] ?? 0);
                $expected = $_POST['expected_arrival'] ?? null;
                if ($tripCode === '' || !$boatId || !$captainId || !$portId) {
                    throw new InvalidArgumentException('كل الحقول (كود الرحلة، القارب، الكابتن، الميناء) مطلوبة.');
                }
                $pdo->prepare(
                    "INSERT INTO trips (trip_code, boat_id, captain_id, port_id, expected_arrival, status)
                     VALUES (?, ?, ?, ?, ?, 'expected')"
                )->execute([$tripCode, $boatId, $captainId, $portId, $expected ?: null]);
                redirectWithMessage($redirectUrl . '?section=trips', 'success', 'تمت إضافة رحلة جديدة (بحالة متوقعة).');
                break;

            case 'mark_arrived':
                $pdo->prepare("UPDATE trips SET status = 'arrived', actual_arrival = NOW() WHERE id = ?")
                    ->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=trips', 'success', 'تم تسجيل وصول القارب.');
                break;

            case 'delete_trip':
                $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([(int)$_POST['id']]);
                redirectWithMessage($redirectUrl . '?section=trips', 'success', 'تم حذف الرحلة.');
                break;

            default:
                redirectWithMessage($redirectUrl . '?section=' . $section, 'error', 'إجراء غير معروف.');
        }
    } catch (InvalidArgumentException $e) {
        redirectWithMessage($redirectUrl . '?section=' . $section, 'error', $e->getMessage());
    } catch (Throwable $e) {
        error_log('Master data error: ' . $e->getMessage());
        redirectWithMessage($redirectUrl . '?section=' . $section, 'error', 'حدث خطأ (ربما قيمة مكررة أو مرتبطة ببيانات أخرى).');
    }
}

/* ------------------------------------------------------------
   جلب البيانات لكل قسم
------------------------------------------------------------ */
$regions = $pdo->query("SELECT * FROM regions ORDER BY name")->fetchAll();
$governorates = $pdo->query(
    "SELECT g.*, r.name AS region_name FROM governorates g JOIN regions r ON r.id = g.region_id ORDER BY r.name, g.name"
)->fetchAll();
$ports = $pdo->query(
    "SELECT p.*, g.name AS gov_name FROM ports p JOIN governorates g ON g.id = p.governorate_id ORDER BY g.name, p.name"
)->fetchAll();
$boats = $pdo->query(
    "SELECT b.*, p.name AS port_name FROM boats b LEFT JOIN ports p ON p.id = b.home_port_id ORDER BY b.name"
)->fetchAll();
$captains = $pdo->query("SELECT * FROM captains ORDER BY full_name")->fetchAll();
$species = $pdo->query("SELECT * FROM fish_species ORDER BY name_ar")->fetchAll();
$trips = $pdo->query(
    "SELECT t.*, b.name AS boat_name, c.full_name AS captain_name, p.name AS port_name
     FROM trips t JOIN boats b ON b.id = t.boat_id JOIN captains c ON c.id = t.captain_id JOIN ports p ON p.id = t.port_id
     ORDER BY t.id DESC LIMIT 100"
)->fetchAll();

$statusLabels = [
    'expected' => 'متوقعة', 'arrived' => 'واصلة', 'waiting_employee' => 'بانتظار موظف',
    'counting' => 'تحت الإحصاء', 'pending_review' => 'بانتظار مراجعة', 'approved' => 'معتمدة', 'closed' => 'مغلقة',
];

$sections = [
    'regions'      => 'المناطق',
    'governorates' => 'المحافظات',
    'ports'        => 'الموانئ',
    'boats'        => 'القوارب',
    'captains'     => 'الكباتن',
    'species'      => 'أنواع الأسماك',
    'trips'        => 'الرحلات',
];

require __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="padding:14px 18px;">
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php foreach ($sections as $key => $label): ?>
            <a href="?section=<?= e($key) ?>" class="btn <?= $section === $key ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($section === 'regions'): ?>
<div class="panel">
    <h3>إضافة منطقة جديدة</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_region">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">اسم المنطقة</label>
            <input type="text" name="name" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <button type="submit" class="btn btn-primary">إضافة</button>
    </form>
</div>
<div class="panel">
    <h3>المناطق الحالية (<?= count($regions) ?>)</h3>
    <table>
        <thead><tr><th>الاسم</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($regions as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('حذف هذه المنطقة؟ (يفشل لو مرتبطة بمحافظات)');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_region">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($section === 'governorates'): ?>
<div class="panel">
    <h3>إضافة محافظة جديدة</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_governorate">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">المنطقة</label>
            <select name="region_id" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
                <option value="">اختر منطقة</option>
                <?php foreach ($regions as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">اسم المحافظة</label>
            <input type="text" name="name" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <button type="submit" class="btn btn-primary">إضافة</button>
    </form>
    <?php if (empty($regions)): ?><p class="panel-hint">أضف منطقة أولًا من تبويب "المناطق".</p><?php endif; ?>
</div>
<div class="panel">
    <h3>المحافظات الحالية (<?= count($governorates) ?>)</h3>
    <table>
        <thead><tr><th>الاسم</th><th>المنطقة</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($governorates as $g): ?>
            <tr>
                <td><?= e($g['name']) ?></td>
                <td><?= e($g['region_name']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('حذف هذه المحافظة؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_governorate">
                        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($section === 'ports'): ?>
<div class="panel">
    <h3>إضافة ميناء جديد</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_port">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">المحافظة</label>
            <select name="governorate_id" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
                <option value="">اختر محافظة</option>
                <?php foreach ($governorates as $g): ?><option value="<?= (int)$g['id'] ?>"><?= e($g['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">اسم الميناء</label>
            <input type="text" name="name" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <label style="display:flex; align-items:center; gap:6px; font-size:13px;">
            <input type="checkbox" name="is_active" checked> نشط
        </label>
        <button type="submit" class="btn btn-primary">إضافة</button>
    </form>
    <?php if (empty($governorates)): ?><p class="panel-hint">أضف محافظة أولًا.</p><?php endif; ?>
</div>
<div class="panel">
    <h3>الموانئ الحالية (<?= count($ports) ?>)</h3>
    <table>
        <thead><tr><th>الاسم</th><th>المحافظة</th><th>الحالة</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($ports as $p): ?>
            <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['gov_name']) ?></td>
                <td><span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $p['is_active'] ? 'نشط' : 'غير نشط' ?></span></td>
                <td style="display:flex; gap:6px;">
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_port">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm"><?= $p['is_active'] ? 'تعطيل' : 'تفعيل' ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('حذف هذا الميناء؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_port">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($section === 'boats'): ?>
<div class="panel">
    <h3>إضافة قارب جديد</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_boat">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">اسم القارب</label>
            <input type="text" name="name" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">رقم التسجيل (اختياري)</label>
            <input type="text" name="registration_no" style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">ميناء التسجيل (اختياري)</label>
            <select name="home_port_id" style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
                <option value="">بدون</option>
                <?php foreach ($ports as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">إضافة</button>
    </form>
</div>
<div class="panel">
    <h3>القوارب الحالية (<?= count($boats) ?>)</h3>
    <table>
        <thead><tr><th>الاسم</th><th>رقم التسجيل</th><th>ميناء التسجيل</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($boats as $b): ?>
            <tr>
                <td><?= e($b['name']) ?></td>
                <td><?= $b['registration_no'] ? e($b['registration_no']) : '—' ?></td>
                <td><?= $b['port_name'] ? e($b['port_name']) : '—' ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('حذف هذا القارب؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_boat">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($section === 'captains'): ?>
<div class="panel">
    <h3>إضافة كابتن جديد</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_captain">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الاسم الكامل</label>
            <input type="text" name="full_name" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">رقم الهوية (اختياري)</label>
            <input type="text" name="national_id" style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الجوال (اختياري)</label>
            <input type="text" name="phone" style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <button type="submit" class="btn btn-primary">إضافة</button>
    </form>
</div>
<div class="panel">
    <h3>الكباتن الحاليون (<?= count($captains) ?>)</h3>
    <table>
        <thead><tr><th>الاسم</th><th>رقم الهوية</th><th>الجوال</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($captains as $c): ?>
            <tr>
                <td><?= e($c['full_name']) ?></td>
                <td><?= $c['national_id'] ? e($c['national_id']) : '—' ?></td>
                <td><?= $c['phone'] ? e($c['phone']) : '—' ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('حذف هذا الكابتن؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_captain">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($section === 'species'): ?>
<div class="panel">
    <h3>إضافة نوع سمك جديد</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_species">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">اسم النوع</label>
            <input type="text" name="name_ar" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <button type="submit" class="btn btn-primary">إضافة</button>
    </form>
</div>
<div class="panel">
    <h3>الأنواع الحالية (<?= count($species) ?>)</h3>
    <table>
        <thead><tr><th>الاسم</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($species as $s): ?>
            <tr>
                <td><?= e($s['name_ar']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('حذف هذا النوع؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_species">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($section === 'trips'): ?>
<div class="panel">
    <h3>إضافة رحلة جديدة (تدخل بحالة "متوقعة")</h3>
    <form method="post" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_trip">
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">كود الرحلة</label>
            <input type="text" name="trip_code" placeholder="TR-1001" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">القارب</label>
            <select name="boat_id" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
                <option value="">اختر قارب</option>
                <?php foreach ($boats as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الكابتن</label>
            <select name="captain_id" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
                <option value="">اختر كابتن</option>
                <?php foreach ($captains as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['full_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الميناء</label>
            <select name="port_id" required style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
                <option value="">اختر ميناء</option>
                <?php foreach ($ports as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; margin-bottom:4px;">الوصول المتوقع</label>
            <input type="datetime-local" name="expected_arrival" style="padding:9px 12px; border:1px solid var(--line); border-radius:9px; background:var(--bg-2); color:var(--ink);">
        </div>
        <button type="submit" class="btn btn-primary">إضافة الرحلة</button>
    </form>
    <?php if (empty($boats) || empty($captains) || empty($ports)): ?>
        <p class="panel-hint">تحتاج تضيف قارب وكابتن وميناء أولًا قبل إضافة رحلة.</p>
    <?php endif; ?>
</div>
<div class="panel">
    <h3>آخر 100 رحلة</h3>
    <div style="overflow-x:auto;">
    <table>
        <thead><tr><th>الكود</th><th>القارب</th><th>الكابتن</th><th>الميناء</th><th>الحالة</th><th>الوصول المتوقع</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($trips as $t): ?>
            <tr>
                <td><?= e($t['trip_code']) ?></td>
                <td><?= e($t['boat_name']) ?></td>
                <td><?= e($t['captain_name']) ?></td>
                <td><?= e($t['port_name']) ?></td>
                <td><span class="badge badge-info"><?= e($statusLabels[$t['status']] ?? $t['status']) ?></span></td>
                <td><?= $t['expected_arrival'] ? e($t['expected_arrival']) : '—' ?></td>
                <td style="display:flex; gap:6px;">
                    <?php if ($t['status'] === 'expected'): ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="mark_arrived">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">تسجيل وصول</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('حذف هذه الرحلة نهائيًا؟');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_trip">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
