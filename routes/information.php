<?php

use App\Http\Controllers\InformationAdminController;
use App\Http\Controllers\InformationIdentityController;
use App\Http\Controllers\InformationPortalController;
use App\Http\Controllers\InformationStatusController;
use Illuminate\Support\Facades\Route;

$informationPortal = function (): void {
    Route::get('/', [InformationIdentityController::class, 'create'])->name('identity.create');
    Route::post('/', [InformationIdentityController::class, 'store'])->middleware('throttle:10,1')->name('identity.store');
    Route::post('/exit', [InformationIdentityController::class, 'destroy'])->name('identity.destroy');

    Route::middleware('information.identity')->group(function (): void {
        Route::get('/status', [InformationStatusController::class, 'index'])->name('status.index');
        Route::get('/new', [InformationPortalController::class, 'create'])->name('create');
        Route::post('/new', [InformationPortalController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/submitted/{reference}', [InformationPortalController::class, 'submitted'])->name('submitted');
    });

    Route::prefix('/admin')
        ->name('admin.')
        ->middleware(['auth', 'role:super_admin,quality_supervisor'])
        ->group(function (): void {
            Route::get('/', [InformationAdminController::class, 'index'])->name('index');
            Route::get('/{submission}', [InformationAdminController::class, 'show'])->name('show');
            Route::patch('/{submission}/review', [InformationAdminController::class, 'review'])->name('review');
            Route::get('/{submission}/documents/{category}', [InformationAdminController::class, 'document'])->name('documents.show');
        });
};

$informationDomain = config('information.domain');

if (is_string($informationDomain) && $informationDomain !== '') {
    Route::domain($informationDomain)->name('information.')->group($informationPortal);
} else {
    Route::prefix('info')->name('information.')->group($informationPortal);
}
