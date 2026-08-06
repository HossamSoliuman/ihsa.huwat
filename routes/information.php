<?php

use App\Http\Controllers\InformationAdminController;
use App\Http\Controllers\InformationDashboardController;
use App\Http\Controllers\InformationIdentityController;
use App\Http\Controllers\InformationLookupController;
use App\Http\Controllers\InformationPortalController;
use App\Http\Controllers\InformationStatusController;
use App\Models\LookupList;
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
            Route::get('/dashboard', InformationDashboardController::class)->name('dashboard');

            /** Registered ahead of the "/{submission}" route so the desk keeps the shorter paths. */
            Route::prefix('/lookups')->name('lookups.')->group(function (): void {
                Route::get('/', [InformationLookupController::class, 'index'])->name('index');

                /** Every option list owns a table, so the list being edited names itself in the path. */
                Route::prefix('/lists/{list}')
                    ->name('options.')
                    ->whereIn('list', array_keys(LookupList::LISTS))
                    ->group(function (): void {
                        Route::post('/', [InformationLookupController::class, 'storeOption'])->name('store');
                        Route::patch('/{option}', [InformationLookupController::class, 'updateOption'])->whereNumber('option')->name('update');
                        Route::patch('/{option}/status', [InformationLookupController::class, 'toggleOption'])->whereNumber('option')->name('toggle');
                        Route::delete('/{option}', [InformationLookupController::class, 'destroyOption'])->whereNumber('option')->name('destroy');
                    });

                Route::prefix('/references/{type}')
                    ->name('references.')
                    ->whereIn('type', array_keys(InformationLookupController::REFERENCES))
                    ->group(function (): void {
                        Route::post('/', [InformationLookupController::class, 'storeReference'])->name('store');
                        Route::patch('/{record}/status', [InformationLookupController::class, 'toggleReference'])->whereNumber('record')->name('toggle');
                        Route::delete('/{record}', [InformationLookupController::class, 'destroyReference'])->whereNumber('record')->name('destroy');
                    });
            });

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
