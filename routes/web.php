<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminResourceController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnualBulletinController;
use App\Http\Controllers\ApprovedCatchController;
use App\Http\Controllers\BoatController;
use App\Http\Controllers\BoatTimelineController;
use App\Http\Controllers\BycatchController;
use App\Http\Controllers\CatchTraceController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscrepancyReviewController;
use App\Http\Controllers\ExecutiveBriefingController;
use App\Http\Controllers\FieldStatisticsController;
use App\Http\Controllers\FisherController;
use App\Http\Controllers\FishingSeasonController;
use App\Http\Controllers\FishingSiteController;
use App\Http\Controllers\FoodSecurityController;
use App\Http\Controllers\GovernorateController;
use App\Http\Controllers\IntegrationSettingController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MonthlyReportsController;
use App\Http\Controllers\NationalIndicatorsController;
use App\Http\Controllers\PerformanceCompareController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SeaMapController;
use App\Http\Controllers\SeasonLicenseController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\StatisticsOfficerController;
use App\Http\Controllers\StatisticsPortalController;
use App\Http\Controllers\SupplyChainController;
use App\Http\Controllers\SustainabilityController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| بوابة المعلومات — مركز إدارة النظام
|--------------------------------------------------------------------------
|
| تُسجَّل قبل لوحة الوزارة عمدًا: المسار بلا قيد نطاق يلتقط أي مضيف، فلو جاءت
| مسارات اللوحة أولًا لابتلعت "/" على info.hawat.sa قبل أن تصل إلى هنا.
|
| عند ترك INFO_PORTAL_DOMAIN فارغًا تعمل البوابة تحت البادئة /info بدل مضيف مستقل.
|
*/

$infoPortal = function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    // تبويب مجهول يُردّ بـ 404 من الموجّه بدل أن يصل إلى السجل فيرمي استثناءً.
    Route::prefix('admin')->name('admin.')->whereIn('tab', array_keys(config('info.tabs')))->group(function (): void {
        Route::get('{tab}', [AdminController::class, 'show'])->name('tab');

        Route::post('{tab}/{resource}', [AdminResourceController::class, 'store'])->name('resource.store');
        Route::put('{tab}/{resource}/{id}', [AdminResourceController::class, 'update'])->name('resource.update');
        Route::delete('{tab}/{resource}/{id}', [AdminResourceController::class, 'destroy'])->name('resource.destroy');

        Route::put('{tab}/integration/{provider}', [IntegrationSettingController::class, 'update'])->name('integration.update');
    });
};

$infoDomain = config('info.domain');
$onSeparateHost = is_string($infoDomain) && $infoDomain !== '';

if ($onSeparateHost) {
    Route::domain($infoDomain)->group($infoPortal);
} else {
    Route::prefix('info')->group($infoPortal);
}

/*
|--------------------------------------------------------------------------
| لوحة الوزارة — النطاق الرئيسي
|--------------------------------------------------------------------------
|
| حين تعمل البوابة على مضيف مستقل تُقيَّد اللوحة بنطاقها الرئيسي، وإلا لظهرت
| صفحاتها أيضًا على info.hawat.sa. وإن لم يُعرف النطاق الرئيسي تبقى بلا قيد.
|
*/

/*
 * لوحة الحكومة التنفيذية — تحت البادئة /gov بقائمة جانبية خاصة بها
 * (config/hawat.php → nav_gov). صفحاتها لم تعد تُقدَّم من المسارات العليا.
 */
$govDashboard = function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/sea-map', [SeaMapController::class, 'index'])->name('sea-map');

    Route::get('/production', [ProductionController::class, 'index'])->name('production');
    Route::get('/ports-compare', [PortController::class, 'compare'])->name('ports-compare');

    Route::get('/sustainability', [SustainabilityController::class, 'index'])->name('sustainability');

    Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance');
    Route::post('/compliance', [ComplianceController::class, 'store'])->name('compliance.store');

    // شاشات لم تُبنَ بعد ضمن لوحة الحكومة.
    Route::get('/alerts', fn () => view('pages.pending', ['routeName' => 'gov.alerts']))->name('alerts');
};

/*
 * قسم الإحصاء — بوابة قائمة بذاتها تحت البادئة /stats.
 *
 * لوحاته الست عشرة كانت موزّعة بين لوحة الحكومة والمنصة التشغيلية، فجُمعت هنا
 * ليصبح للقسم قائمته الجانبية وحده: من الرصد الميداني والاعتماد، إلى التحليل
 * والتقارير، إلى الأسواق والأمن الغذائي.
 */
$statisticsSection = function (): void {
    Route::get('/', [StatisticsPortalController::class, 'index'])->name('home');

    Route::get('/executive-briefing', [ExecutiveBriefingController::class, 'index'])->name('executive-briefing');
    Route::get('/executive-briefing/export.csv', [ExecutiveBriefingController::class, 'exportCsv'])->name('executive-briefing.csv');
    Route::get('/executive-briefing/export.json', [ExecutiveBriefingController::class, 'exportJson'])->name('executive-briefing.json');

    Route::get('/national-indicators', [NationalIndicatorsController::class, 'index'])->name('national-indicators');
    Route::get('/performance-compare', [PerformanceCompareController::class, 'index'])->name('performance-compare');

    Route::get('/field-statistics', [FieldStatisticsController::class, 'index'])->name('field-statistics');
    Route::post('/field-statistics/{trip}/record', [FieldStatisticsController::class, 'record'])->name('field-statistics.record');

    Route::get('/approved-catch', [ApprovedCatchController::class, 'index'])->name('approved-catch');
    Route::post('/approved-catch/{trip}/approve', [ApprovedCatchController::class, 'approve'])->name('approved-catch.approve');

    Route::get('/statistics-officers', [StatisticsOfficerController::class, 'index'])->name('statistics-officers');
    Route::get('/catch-trace', [CatchTraceController::class, 'index'])->name('catch-trace');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/reports/{report}/export.csv', [ReportsController::class, 'export'])->name('reports.export');
    Route::get('/reports/{report}/print', [ReportsController::class, 'print'])->name('reports.print');

    Route::get('/monthly-reports', [MonthlyReportsController::class, 'index'])->name('monthly-reports');
    Route::get('/monthly-reports/export.csv', [MonthlyReportsController::class, 'export'])->name('monthly-reports.export');
    Route::get('/monthly-reports/print', [MonthlyReportsController::class, 'print'])->name('monthly-reports.print');

    Route::get('/annual-bulletin', [AnnualBulletinController::class, 'index'])->name('annual-bulletin');
    Route::get('/annual-bulletin/print', [AnnualBulletinController::class, 'print'])->name('annual-bulletin.print');

    Route::get('/markets', [MarketController::class, 'index'])->name('markets');
    Route::get('/supply-chain', [SupplyChainController::class, 'index'])->name('supply-chain');
    Route::get('/food-security', [FoodSecurityController::class, 'index'])->name('food-security');
};

/*
 * المنصة التشغيلية — السجلات والعمليات، تحت البادئة /admin.
 *
 * أسماء المسارات تبقى بلا بادئة (governorates، boats…) لأن البادئة `admin.`
 * محجوزة لبوابة المعلومات أعلاه (admin.index، admin.tab)، ولأن القائمة الجانبية
 * والاختبارات تشير إلى هذه الأسماء لا إلى مساراتها.
 */
$operationsConsole = function (): void {
    Route::get('/governorates', [GovernorateController::class, 'index'])->name('governorates');
    Route::get('/governorates/{governorate:name}', [GovernorateController::class, 'show'])->name('governorates.show');

    Route::get('/regions', [RegionController::class, 'index'])->name('regions');
    Route::post('/regions', [RegionController::class, 'store'])->name('regions.store');
    Route::put('/regions/{region}', [RegionController::class, 'update'])->name('regions.update');
    Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');

    Route::get('/species', [SpeciesController::class, 'index'])->name('species');
    Route::put('/species/{species}', [SpeciesController::class, 'update'])->name('species.update');

    Route::get('/fishing-seasons', [FishingSeasonController::class, 'index'])->name('fishing-seasons');
    Route::post('/fishing-seasons', [FishingSeasonController::class, 'store'])->name('fishing-seasons.store');
    Route::put('/fishing-seasons/{fishingSeason}', [FishingSeasonController::class, 'update'])->name('fishing-seasons.update');
    Route::post('/fishing-seasons/{fishingSeason}/status', [FishingSeasonController::class, 'updateStatus'])->name('fishing-seasons.status');

    Route::get('/season-licenses', [SeasonLicenseController::class, 'index'])->name('season-licenses');
    Route::post('/season-licenses', [SeasonLicenseController::class, 'store'])->name('season-licenses.store');
    Route::put('/season-licenses/{seasonLicense}', [SeasonLicenseController::class, 'update'])->name('season-licenses.update');

    Route::get('/boats', [BoatController::class, 'index'])->name('boats');
    Route::get('/fishers', [FisherController::class, 'index'])->name('fishers');
    Route::get('/trips', [TripController::class, 'index'])->name('trips');
    Route::get('/boat-timeline', [BoatTimelineController::class, 'index'])->name('boat-timeline');
    Route::get('/ports', [PortController::class, 'index'])->name('ports');
    Route::get('/fishing-sites', [FishingSiteController::class, 'index'])->name('fishing-sites');

    Route::get('/discrepancy-review', [DiscrepancyReviewController::class, 'index'])->name('discrepancy-review');
    Route::post('/discrepancy-review/{trip}/resolve', [DiscrepancyReviewController::class, 'resolve'])->name('discrepancy-review.resolve');

    Route::get('/bycatch', [BycatchController::class, 'index'])->name('bycatch');
    Route::post('/bycatch', [BycatchController::class, 'store'])->name('bycatch.store');

    // شاشات لم تُبنَ بعد — "مركز الإدارة" ليس منها، فهو يشير إلى بوابة المعلومات.
    foreach (['audit-log', 'users', 'settings'] as $slug) {
        Route::get('/'.$slug, fn () => view('pages.pending', ['routeName' => $slug]))->name($slug);
    }
};

/*
 * البوابات الثلاث تتشارك النطاق الرئيسي: صفحة اختيار على "/"، ثم لوحة الحكومة
 * تحت /gov، وقسم الإحصاء تحت /stats، والمنصة التشغيلية تحت /admin.
 */
$governmentPortal = function () use ($govDashboard, $statisticsSection, $operationsConsole): void {
    Route::view('/', 'portal')->name('portal');

    Route::prefix('gov')->name('gov.')->group($govDashboard);

    Route::prefix('stats')->name('stats.')->group($statisticsSection);

    Route::prefix('admin')->group($operationsConsole);

    /*
     * مواضع لوحات قسم الإحصاء قبل استقلاله ببوابته. التحويل دائم حفاظًا على
     * الروابط المحفوظة والمُرسلة، وليست مسارات مكرّرة: لا شيء يُقدَّم منها.
     */
    foreach ([
        '/gov/statistics' => '/stats',
        '/gov/executive-briefing' => '/stats/executive-briefing',
        '/gov/national-indicators' => '/stats/national-indicators',
        '/gov/performance-compare' => '/stats/performance-compare',
        '/gov/field-statistics' => '/stats/field-statistics',
        '/gov/approved-catch' => '/stats/approved-catch',
        '/gov/ai-assistant' => '/stats/ai-assistant',
        '/gov/reports' => '/stats/reports',
        '/gov/monthly-reports' => '/stats/monthly-reports',
        '/gov/annual-bulletin' => '/stats/annual-bulletin',
        '/gov/food-security' => '/stats/food-security',
        '/admin/statistics-officers' => '/stats/statistics-officers',
        '/admin/catch-trace' => '/stats/catch-trace',
        '/admin/analytics' => '/stats/analytics',
        '/admin/markets' => '/stats/markets',
        '/admin/supply-chain' => '/stats/supply-chain',
    ] as $vacated => $destination) {
        Route::permanentRedirect($vacated, $destination);
    }
};

$governmentDomain = config('hawat.domain');

if ($onSeparateHost && is_string($governmentDomain) && $governmentDomain !== '' && $governmentDomain !== $infoDomain) {
    Route::domain($governmentDomain)->group($governmentPortal);
} else {
    $governmentPortal();
}
