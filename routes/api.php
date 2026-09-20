<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\Owner\BoatController as OwnerBoatController;
use App\Http\Controllers\Api\V1\Owner\CaptainController as OwnerCaptainController;
use App\Http\Controllers\Api\V1\Owner\ConsignmentController as OwnerConsignmentController;
use App\Http\Controllers\Api\V1\Owner\CrewController as OwnerCrewController;
use App\Http\Controllers\Api\V1\Owner\CustomerController as OwnerCustomerController;
use App\Http\Controllers\Api\V1\Owner\DalalController as OwnerDalalController;
use App\Http\Controllers\Api\V1\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Api\V1\Owner\EmployeeController as OwnerEmployeeController;
use App\Http\Controllers\Api\V1\Owner\MaintenanceController as OwnerMaintenanceController;
use App\Http\Controllers\Api\V1\Owner\SaleController as OwnerSaleController;
use App\Http\Controllers\Api\V1\Owner\StockController as OwnerStockController;
use App\Http\Controllers\Api\V1\Owner\TripController as OwnerTripController;
use App\Http\Controllers\Api\V1\Owner\VendorController as OwnerVendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| واجهة تطبيق حوات — /api/v1
|--------------------------------------------------------------------------
|
| رموز Sanctum في ترويسة Authorization: Bearer، والردّ JSON دائمًا (ForceJsonResponse).
| المسارات مجمّعة بالدور كما شاشات التطبيق: auth للجميع، ثم owner و captain
| و counter و dalal كلٌّ تحت وسيط دوره — تُضاف مع بناء بوابة كل دور.
|
| التوثيق: docs/api/openapi.yaml ويُعرض على /api/docs.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

        // استعادة كلمة المرور بالجوال: رمز، فتحقق، فكلمة جديدة.
        Route::post('forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:otp')->name('forgot-password');
        Route::post('verify-otp', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('verify-otp');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('reset-password');

        Route::middleware(['auth:sanctum', 'api.active'])->group(function (): void {
            Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
            Route::get('me', [MeController::class, 'show'])->name('me');
            Route::put('me', [MeController::class, 'update'])->name('me.update');
            Route::post('change-password', [MeController::class, 'changePassword'])->name('change-password');
            Route::post('fcm-token', [MeController::class, 'updateFcmToken'])->name('fcm-token');
        });
    });

    Route::middleware(['auth:sanctum', 'api.active'])->group(function (): void {
        // القوائم المرجعية لكل الأدوار في ردّ واحد.
        Route::get('lookups', [LookupController::class, 'index'])->name('lookups');

        /*
         * بوابة المالك: الأسطول والطاقم والعملاء، ثم الرحلات بدورتها
         * والبيع والإرسال للدلال. كل سجل مقيّد بمالكه (404 لغيره).
         */
        Route::prefix('owner')->name('owner.')->middleware('api.role:owner')->group(function (): void {
            Route::get('dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');

            Route::apiResource('boats', OwnerBoatController::class);
            Route::apiResource('maintenance', OwnerMaintenanceController::class)->except('show');
            Route::apiResource('captains', OwnerCaptainController::class)->except('destroy');
            Route::apiResource('crew', OwnerCrewController::class)->except('show');
            Route::apiResource('employees', OwnerEmployeeController::class)->parameters(['employees' => 'id']);
            Route::apiResource('customers', OwnerCustomerController::class)->parameters(['customers' => 'id']);
            Route::apiResource('vendors', OwnerVendorController::class)->parameters(['vendors' => 'id']);

            Route::apiResource('trips', OwnerTripController::class)->except('destroy');
            Route::post('trips/{trip}/start', [OwnerTripController::class, 'start'])->name('trips.start');
            Route::post('trips/{trip}/cancel', [OwnerTripController::class, 'cancel'])->name('trips.cancel');
            Route::post('trips/{trip}/catch', [OwnerTripController::class, 'submitCatch'])->name('trips.catch');

            Route::apiResource('sales', OwnerSaleController::class)->only(['index', 'store', 'show']);
            Route::apiResource('consignments', OwnerConsignmentController::class)->only(['index', 'store', 'show']);

            Route::get('dalals', [OwnerDalalController::class, 'index'])->name('dalals');
            Route::get('stock', [OwnerStockController::class, 'index'])->name('stock');
            Route::get('stock/movements', [OwnerStockController::class, 'movements'])->name('stock.movements');
        });
    });
});
