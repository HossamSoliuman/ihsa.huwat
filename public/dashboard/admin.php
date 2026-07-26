<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin']);
$pageTitle = 'الرئيسية - الإدارة العليا';
$activeRoute = 'admin.php';
$hidePageHeading = true;
$bodyClass = 'admin-dashboard';

$pdo = db();

$kpi = [
    'regions' => (int)$pdo->query("SELECT COUNT(*) FROM regions")->fetchColumn(),
    'governorates' => (int)$pdo->query("SELECT COUNT(*) FROM governorates")->fetchColumn(),
    'ports' => (int)$pdo->query("SELECT COUNT(*) FROM ports WHERE is_active = 1")->fetchColumn(),
    'employees' => (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn(),
    'boats_today' => (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(actual_arrival) = CURDATE()")->fetchColumn(),
    'catch_today' => (float)$pdo->query("SELECT COALESCE(SUM(verified_weight),0) FROM trips WHERE DATE(actual_arrival) = CURDATE() AND status IN ('approved','closed')")->fetchColumn(),
    'diff_trips' => (int)$pdo->query("SELECT COUNT(DISTINCT trip_id) FROM trip_discrepancies WHERE review_status != 'approved'")->fetchColumn(),
    'uncovered_ports' => (int)$pdo->query(
        "SELECT COUNT(*) FROM ports p WHERE p.is_active = 1 AND NOT EXISTS (
            SELECT 1 FROM employee_assignments ea WHERE ea.port_id = p.id AND ea.assignment_date = CURDATE()
        )"
    )->fetchColumn(),
];

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

$topSpecies = $pdo->query(
    "SELECT fs.name_ar, SUM(cd.verified_kg) AS total_kg
     FROM catch_details cd
     JOIN fish_species fs ON fs.id = cd.species_id
     JOIN trips t ON t.id = cd.trip_id AND t.status IN ('approved','closed')
     GROUP BY fs.id, fs.name_ar
     ORDER BY total_kg DESC LIMIT 5"
)->fetchAll();

$alerts = $pdo->query(
    "SELECT * FROM alerts WHERE is_resolved = 0 ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$regionMax = 0.0;
foreach ($regionProduction as $regionRow) {
    $regionMax = max($regionMax, (float)$regionRow['total_kg']);
}

$speciesTotal = 0.0;
foreach ($topSpecies as $speciesRow) {
    $speciesTotal += (float)$speciesRow['total_kg'];
}
$speciesColors = ['#00d4a8', '#38a990', '#337f74', '#315f5c', '#344d50'];
$speciesSegments = [];
$speciesCursor = 0.0;
foreach ($topSpecies as $index => $speciesRow) {
    $nextCursor = $speciesTotal > 0
        ? $speciesCursor + (((float)$speciesRow['total_kg'] / $speciesTotal) * 100)
        : $speciesCursor;
    $color = $speciesColors[$index % count($speciesColors)];
    $speciesSegments[] = $color . ' ' . round($speciesCursor, 2) . '% ' . round($nextCursor, 2) . '%';
    $speciesCursor = $nextCursor;
}
$speciesGradient = $speciesSegments
    ? implode(', ', $speciesSegments)
    : 'var(--donut-empty) 0 100%';

require __DIR__ . '/../../includes/header.php';
?>

<section class="hud-stats" aria-label="ملخص مؤشرات الإدارة">
    <article class="hud-stat-card">
        <div class="hud-card-title"><span>النطاق الجغرافي</span></div>
        <div class="hud-stat-main">
            <span class="hud-stat-value"><?= numberAr($kpi['regions']) ?></span>
            <div class="hud-mini-bars <?= $kpi['regions'] > 0 ? '' : 'is-zero' ?>" aria-hidden="true"><i style="--h:42%"></i><i style="--h:63%"></i><i style="--h:51%"></i><i style="--h:78%"></i><i style="--h:60%"></i><i style="--h:84%"></i><i style="--h:70%"></i><i style="--h:91%"></i></div>
        </div>
        <div class="hud-stat-meta"><span><i class="meta-arrow">↗</i><?= numberAr($kpi['governorates']) ?> محافظة مسجلة</span><span><i class="meta-dot"></i><?= numberAr($kpi['ports']) ?> ميناء نشط</span></div>
    </article>

    <article class="hud-stat-card">
        <div class="hud-card-title"><span>موظفو الإحصاء</span></div>
        <div class="hud-stat-main">
            <span class="hud-stat-value"><?= numberAr($kpi['employees']) ?></span>
            <svg class="hud-sparkline" viewBox="0 0 92 40" preserveAspectRatio="none" aria-hidden="true"><path d="M1 <?= $kpi['employees'] > 0 ? '26 L13 20 L25 24 L37 13 L49 17 L61 8 L73 16 L91 7' : '32 L91 32' ?>"></path></svg>
        </div>
        <div class="hud-stat-meta"><span><i class="meta-arrow">↗</i>قوة العمل النشطة حاليًا</span><span><i class="meta-dot"></i><?= numberAr($kpi['uncovered_ports']) ?> موانئ تحتاج تغطية</span></div>
    </article>

    <article class="hud-stat-card">
        <div class="hud-card-title"><span>حركة اليوم</span></div>
        <div class="hud-stat-main">
            <span class="hud-stat-value"><?= numberAr($kpi['boats_today']) ?></span>
            <svg class="hud-sparkline" viewBox="0 0 92 40" preserveAspectRatio="none" aria-hidden="true"><path d="M1 <?= $kpi['boats_today'] > 0 ? '19 L12 15 L23 21 L35 17 L46 29 L58 8 L70 25 L81 23 L91 14' : '32 L91 32' ?>"></path></svg>
        </div>
        <div class="hud-stat-meta"><span><i class="meta-arrow">↗</i>قارب وصل إلى الموانئ</span><span><i class="meta-dot"></i><?= numberAr($kpi['catch_today']) ?> كجم مصيد موثق</span></div>
    </article>

    <article class="hud-stat-card <?= ($kpi['diff_trips'] + $kpi['uncovered_ports']) > 0 ? 'has-warning' : '' ?>">
        <div class="hud-card-title"><span>جودة العمليات</span></div>
        <div class="hud-stat-main">
            <span class="hud-stat-value"><?= numberAr($kpi['diff_trips']) ?></span>
            <span class="hud-ring" style="--value:<?= min(100, $kpi['diff_trips'] * 8) ?>" aria-hidden="true"><i></i></span>
        </div>
        <div class="hud-stat-meta"><span><i class="meta-arrow">↗</i>رحلات بانتظار المراجعة</span><span><i class="meta-dot"></i><?= numberAr($kpi['uncovered_ports']) ?> موانئ غير مغطاة</span></div>
    </article>
</section>

<section class="dashboard-analytics-grid">
    <article class="panel analytics-panel production-panel">
        <div class="panel-titlebar"><h3>إنتاج المناطق — آخر 30 يومًا</h3></div>
        <div class="production-chart <?= empty($regionProduction) ? 'is-empty' : '' ?>">
            <div class="chart-scale" aria-hidden="true"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div>
            <div class="chart-plot">
                <span class="chart-gridline" style="--y:0"></span><span class="chart-gridline" style="--y:25"></span><span class="chart-gridline" style="--y:50"></span><span class="chart-gridline" style="--y:75"></span><span class="chart-gridline" style="--y:100"></span>
                <?php if (empty($regionProduction)): ?>
                    <?php for ($i = 0; $i < 8; $i++): ?><i class="chart-bar is-zero" style="--bar:2%"></i><?php endfor; ?>
                <?php else: ?>
                    <?php foreach ($regionProduction as $row): ?>
                        <?php $barHeight = $regionMax > 0 ? max(2, ((float)$row['total_kg'] / $regionMax) * 100) : 2; ?>
                        <i class="chart-bar" style="--bar:<?= round($barHeight, 2) ?>%" title="<?= e($row['region_name']) ?>: <?= numberAr($row['total_kg']) ?> كجم"></i>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (empty($regionProduction)): ?>
            <div class="analytics-empty"><i></i><span>ستظهر حركة الإنتاج فور تسجيل أول رحلة معتمدة</span></div>
        <?php else: ?>
            <div class="region-legend">
                <?php foreach ($regionProduction as $row): ?><span><i></i><?= e($row['region_name']) ?> <b><?= numberAr($row['total_kg']) ?></b></span><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel analytics-panel species-panel">
        <div class="panel-titlebar"><h3>تحليل أنواع المصيد</h3></div>
        <div class="species-analytics">
            <div class="species-donut" style="--species-chart: conic-gradient(<?= e($speciesGradient) ?>)"><span><b><?= numberAr($speciesTotal) ?></b><small>كجم</small></span></div>
            <div class="species-list">
                <?php if (empty($topSpecies)): ?>
                    <div class="analytics-empty"><i></i><span>لا توجد بيانات مصيد مصنفة بعد</span></div>
                <?php else: ?>
                    <?php foreach ($topSpecies as $index => $row): ?>
                        <?php $share = $speciesTotal > 0 ? ((float)$row['total_kg'] / $speciesTotal) * 100 : 0; ?>
                        <div class="species-row"><span><i style="--species-color:<?= e($speciesColors[$index % count($speciesColors)]) ?>"></i><?= e($row['name_ar']) ?></span><b><?= numberAr($row['total_kg']) ?></b><em><?= numberAr(round($share, 1)) ?>%</em></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="analytics-summary"><span><small>إجمالي اليوم</small><b><?= numberAr($kpi['catch_today']) ?> كجم</b></span><span><small>الرحلات الواصلة</small><b><?= numberAr($kpi['boats_today']) ?></b></span></div>
    </article>
</section>

<article class="panel dashboard-alerts">
    <div class="panel-titlebar"><h3>سجل تنبيهات الإدارة</h3></div>
    <?php if (empty($alerts)): ?>
        <div class="alert-log-row is-clear"><span><i></i>لا توجد تنبيهات غير محلولة حاليًا</span><time>الآن</time><b>مستقر</b></div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>النوع</th><th>الرسالة</th><th>الخطورة</th><th>التاريخ</th></tr></thead>
                <tbody>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= e($alert['type']) ?></td>
                        <td><?= e($alert['message']) ?></td>
                        <td><span class="badge <?= $alert['severity'] === 'critical' ? 'badge-danger' : ($alert['severity'] === 'warning' ? 'badge-warning' : 'badge-info') ?>"><?= e($alert['severity']) ?></span></td>
                        <td><?= e($alert['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
