<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin']);
$pageTitle   = 'الرئيسية - الإدارة العليا';
$activeRoute = 'admin.php';

$pdo = db();

// ---- البطاقات الرئيسية ----
$kpi = [
    'regions'      => (int)$pdo->query("SELECT COUNT(*) FROM regions")->fetchColumn(),
    'governorates' => (int)$pdo->query("SELECT COUNT(*) FROM governorates")->fetchColumn(),
    'ports'        => (int)$pdo->query("SELECT COUNT(*) FROM ports WHERE is_active = 1")->fetchColumn(),
    'employees'    => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn(),
    'boats_today'  => (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(actual_arrival) = CURDATE()")->fetchColumn(),
    'catch_today'  => (float)$pdo->query("SELECT COALESCE(SUM(verified_weight),0) FROM trips WHERE DATE(actual_arrival) = CURDATE() AND status IN ('approved','closed')")->fetchColumn(),
    'diff_trips'   => (int)$pdo->query("SELECT COUNT(DISTINCT trip_id) FROM trip_discrepancies WHERE review_status != 'approved'")->fetchColumn(),
    'uncovered_ports' => (int)$pdo->query(
        "SELECT COUNT(*) FROM ports p WHERE p.is_active = 1 AND NOT EXISTS (
            SELECT 1 FROM employee_assignments ea WHERE ea.port_id = p.id AND ea.assignment_date = CURDATE()
        )"
    )->fetchColumn(),
];

// ---- مقارنة الإنتاج بين المناطق (آخر 30 يوم) ----
$regionProduction = $pdo->query(
    "SELECT r.name AS region_name, COALESCE(SUM(t.verified_weight),0) AS total_kg
     FROM regions r
     LEFT JOIN governorates g ON g.region_id = r.id
     LEFT JOIN ports p ON p.governorate_id = g.id
     LEFT JOIN trips t ON t.port_id = p.id AND t.status IN ('approved','closed')
        AND t.actual_arrival >= (NOW() - INTERVAL 30 DAY)
     GROUP BY r.id, r.name
     ORDER BY total_kg DESC"
)->fetchAll();

// ---- أعلى أنواع الأسماك ----
$topSpecies = $pdo->query(
    "SELECT fs.name_ar, SUM(cd.verified_kg) AS total_kg
     FROM catch_details cd
     JOIN fish_species fs ON fs.id = cd.species_id
     JOIN trips t ON t.id = cd.trip_id AND t.status IN ('approved','closed')
     GROUP BY fs.id, fs.name_ar
     ORDER BY total_kg DESC LIMIT 5"
)->fetchAll();

// ---- تنبيهات الإدارة (آخر 8 غير محلولة) ----
$alerts = $pdo->query(
    "SELECT * FROM alerts WHERE is_resolved = 0 ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="kpi-grid">
    <div class="kpi-card"><span class="stat-label">إجمالي المناطق</span><span class="stat-value"><?= numberAr($kpi['regions']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي المحافظات</span><span class="stat-value"><?= numberAr($kpi['governorates']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي الموانئ</span><span class="stat-value"><?= numberAr($kpi['ports']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">موظفو الإحصاء النشطون</span><span class="stat-value"><?= numberAr($kpi['employees']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">القوارب الواصلة اليوم</span><span class="stat-value"><?= numberAr($kpi['boats_today']) ?></span></div>
    <div class="kpi-card"><span class="stat-label">إجمالي مصيد اليوم (كجم)</span><span class="stat-value"><?= numberAr($kpi['catch_today']) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">الرحلات ذات الفروقات</span><span class="stat-value"><?= numberAr($kpi['diff_trips']) ?></span></div>
    <div class="kpi-card alert-tone"><span class="stat-label">الموانئ غير المغطاة اليوم</span><span class="stat-value"><?= numberAr($kpi['uncovered_ports']) ?></span></div>
</div>

<div class="grid-2">
    <div class="panel">
        <h3>مقارنة الإنتاج بين المناطق (آخر 30 يومًا)</h3>
        <?php if (empty($regionProduction)): ?>
            <p class="panel-hint">لا توجد بيانات كافية بعد.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>المنطقة</th><th>إجمالي المصيد (كجم)</th></tr></thead>
                <tbody>
                <?php foreach ($regionProduction as $row): ?>
                    <tr>
                        <td><?= e($row['region_name']) ?></td>
                        <td class="num"><?= numberAr($row['total_kg']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>أعلى أنواع الأسماك إنتاجًا</h3>
        <?php if (empty($topSpecies)): ?>
            <p class="panel-hint">لا توجد بيانات كافية بعد.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>النوع</th><th>الكمية (كجم)</th></tr></thead>
                <tbody>
                <?php foreach ($topSpecies as $row): ?>
                    <tr>
                        <td><?= e($row['name_ar']) ?></td>
                        <td class="num"><?= numberAr($row['total_kg']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <h3>تنبيهات الإدارة</h3>
    <?php if (empty($alerts)): ?>
        <p class="panel-hint">لا توجد تنبيهات غير محلولة حاليًا. ✅</p>
    <?php else: ?>
        <table>
            <thead><tr><th>النوع</th><th>الرسالة</th><th>الخطورة</th><th>التاريخ</th></tr></thead>
            <tbody>
            <?php foreach ($alerts as $a): ?>
                <tr>
                    <td><?= e($a['type']) ?></td>
                    <td><?= e($a['message']) ?></td>
                    <td>
                        <span class="badge <?= $a['severity'] === 'critical' ? 'badge-danger' : ($a['severity'] === 'warning' ? 'badge-warning' : 'badge-info') ?>">
                            <?= e($a['severity']) ?>
                        </span>
                    </td>
                    <td><?= e($a['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
