<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
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
});
