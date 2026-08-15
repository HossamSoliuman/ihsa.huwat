<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminResourceController;
use App\Http\Controllers\ApprovedCatchController;
use App\Http\Controllers\BoatController;
use App\Http\Controllers\BoatTimelineController;
use App\Http\Controllers\BycatchController;
use App\Http\Controllers\CatchTraceController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscrepancyReviewController;
use App\Http\Controllers\FieldStatisticsController;
use App\Http\Controllers\FisherController;
use App\Http\Controllers\FishingSeasonController;
use App\Http\Controllers\FishingSiteController;
use App\Http\Controllers\FoodSecurityController;
use App\Http\Controllers\GovernorateController;
use App\Http\Controllers\IntegrationSettingController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\NationalIndicatorsController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SeaMapController;
use App\Http\Controllers\SeasonLicenseController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\StatisticsOfficerController;
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

$governmentPortal = function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('home');

    Route::get('/national-indicators', [NationalIndicatorsController::class, 'index'])->name('national-indicators');
    Route::get('/sea-map', [SeaMapController::class, 'index'])->name('sea-map');

    Route::get('/governorates', [GovernorateController::class, 'index'])->name('governorates');
    Route::get('/governorates/{governorate:name}', [GovernorateController::class, 'show'])->name('governorates.show');

    Route::get('/regions', [RegionController::class, 'index'])->name('regions');
    Route::post('/regions', [RegionController::class, 'store'])->name('regions.store');
    Route::put('/regions/{region}', [RegionController::class, 'update'])->name('regions.update');
    Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');

    Route::get('/production', [ProductionController::class, 'index'])->name('production');

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
    Route::get('/ports-compare', [PortController::class, 'compare'])->name('ports-compare');
    Route::get('/fishing-sites', [FishingSiteController::class, 'index'])->name('fishing-sites');

    Route::get('/field-statistics', [FieldStatisticsController::class, 'index'])->name('field-statistics');
    Route::post('/field-statistics/{trip}/record', [FieldStatisticsController::class, 'record'])->name('field-statistics.record');

    Route::get('/approved-catch', [ApprovedCatchController::class, 'index'])->name('approved-catch');
    Route::post('/approved-catch/{trip}/approve', [ApprovedCatchController::class, 'approve'])->name('approved-catch.approve');

    Route::get('/statistics-officers', [StatisticsOfficerController::class, 'index'])->name('statistics-officers');
    Route::get('/catch-trace', [CatchTraceController::class, 'index'])->name('catch-trace');

    Route::get('/discrepancy-review', [DiscrepancyReviewController::class, 'index'])->name('discrepancy-review');
    Route::post('/discrepancy-review/{trip}/resolve', [DiscrepancyReviewController::class, 'resolve'])->name('discrepancy-review.resolve');

    Route::get('/sustainability', [SustainabilityController::class, 'index'])->name('sustainability');

    Route::get('/bycatch', [BycatchController::class, 'index'])->name('bycatch');
    Route::post('/bycatch', [BycatchController::class, 'store'])->name('bycatch.store');

    Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance');
    Route::post('/compliance', [ComplianceController::class, 'store'])->name('compliance.store');

    Route::get('/markets', [MarketController::class, 'index'])->name('markets');
    Route::get('/supply-chain', [SupplyChainController::class, 'index'])->name('supply-chain');
    Route::get('/food-security', [FoodSecurityController::class, 'index'])->name('food-security');

    // شاشات لم تُبنَ بعد — "مركز الإدارة" ليس منها، فهو يشير إلى بوابة المعلومات.
    $pending = [
        'analytics', 'ai-assistant', 'alerts',
        'reports', 'monthly-reports', 'annual-bulletin', 'audit-log', 'users', 'settings',
    ];

    foreach ($pending as $slug) {
        Route::get('/'.$slug, fn () => view('pages.pending', ['routeName' => $slug]))->name($slug);
    }
};

$governmentDomain = config('hawat.domain');

if ($onSeparateHost && is_string($governmentDomain) && $governmentDomain !== '' && $governmentDomain !== $infoDomain) {
    Route::domain($governmentDomain)->group($governmentPortal);
} else {
    $governmentPortal();
}
