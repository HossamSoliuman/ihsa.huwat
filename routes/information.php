<?php

use App\Actions\Information\Support\InformationScope;
use App\Http\Controllers\InformationAdminController;
use App\Http\Controllers\InformationBrokerController;
use App\Http\Controllers\InformationDashboardController;
use App\Http\Controllers\InformationIdentityController;
use App\Http\Controllers\InformationLookupController;
use App\Http\Controllers\InformationMarketController;
use App\Http\Controllers\InformationMarketUnitController;
use App\Http\Controllers\InformationMarketWorkerController;
use App\Http\Controllers\InformationModeratorController;
use App\Http\Controllers\InformationPortalController;
use App\Http\Controllers\InformationPortController;
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

    /**
     * The desk and its moderators share every route below. `information.scope` is what tells
     * them apart: it names the section each route belongs to, refuses the sections a
     * moderator's level does not answer for, and 404s any record outside the ones assigned.
     */
    Route::prefix('/admin')
        ->name('admin.')
        ->middleware(['auth', 'role:'.implode(',', config('information.admin_roles')), 'information.scope'])
        ->group(function (): void {
            Route::get('/dashboard', InformationDashboardController::class)->name('dashboard');

            /** المشرفون — accounts the desk opens, and the records each one is pinned to. */
            Route::prefix('/moderators')
                ->name('moderators.')
                ->middleware('information.scope:'.InformationScope::MODERATORS)
                ->whereNumber('moderator')
                ->group(function (): void {
                    Route::get('/', [InformationModeratorController::class, 'index'])->name('index');
                    Route::get('/create', [InformationModeratorController::class, 'create'])->name('create');
                    Route::post('/', [InformationModeratorController::class, 'store'])->name('store');
                    Route::get('/{moderator}', [InformationModeratorController::class, 'show'])->name('show');
                    Route::patch('/{moderator}', [InformationModeratorController::class, 'update'])->name('update');
                    Route::delete('/{moderator}', [InformationModeratorController::class, 'destroy'])->name('destroy');
                });

            /** Registered ahead of the "/{submission}" route so the desk keeps the shorter paths. */
            Route::prefix('/lookups')
                ->name('lookups.')
                ->middleware('information.scope:'.InformationScope::SETTINGS)
                ->group(function (): void {
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

            /** الموانئ — a read-only profile per live port. */
            Route::prefix('/ports')
                ->name('ports.')
                ->middleware('information.scope:'.InformationScope::PORTS)
                ->whereNumber('port')
                ->group(function (): void {
                    Route::get('/', [InformationPortController::class, 'index'])->name('index');
                    Route::get('/{port}', [InformationPortController::class, 'show'])->name('show');
                });

            /**
             * أسواق السمك. The unit is scoped to its market and the worker to its unit, so a
             * mismatched pair 404s instead of reaching another market's records.
             */
            Route::prefix('/markets')
                ->name('markets.')
                ->middleware('information.scope:'.InformationScope::MARKETS)
                ->scopeBindings()
                ->whereNumber(['market', 'unit', 'worker'])
                ->group(function (): void {
                    Route::get('/', [InformationMarketController::class, 'index'])->name('index');
                    Route::get('/create', [InformationMarketController::class, 'create'])->name('create');
                    Route::post('/', [InformationMarketController::class, 'store'])->name('store');
                    Route::get('/{market}', [InformationMarketController::class, 'show'])->name('show');
                    Route::patch('/{market}', [InformationMarketController::class, 'update'])->name('update');
                    Route::delete('/{market}', [InformationMarketController::class, 'destroy'])->name('destroy');

                    Route::post('/{market}/units', [InformationMarketUnitController::class, 'store'])->name('units.store');
                    Route::patch('/{market}/units/{unit}', [InformationMarketUnitController::class, 'update'])->name('units.update');
                    Route::delete('/{market}/units/{unit}', [InformationMarketUnitController::class, 'destroy'])->name('units.destroy');

                    Route::post('/{market}/units/{unit}/workers', [InformationMarketWorkerController::class, 'store'])->name('units.workers.store');
                    Route::patch('/{market}/units/{unit}/workers/{worker}', [InformationMarketWorkerController::class, 'update'])->name('units.workers.update');
                    Route::delete('/{market}/units/{unit}/workers/{worker}', [InformationMarketWorkerController::class, 'destroy'])->name('units.workers.destroy');
                });

            /** الدلالين — flat records, each attached to one market. */
            Route::prefix('/brokers')
                ->name('brokers.')
                ->middleware('information.scope:'.InformationScope::BROKERS)
                ->whereNumber('broker')
                ->group(function (): void {
                    Route::get('/', [InformationBrokerController::class, 'index'])->name('index');
                    Route::get('/create', [InformationBrokerController::class, 'create'])->name('create');
                    Route::post('/', [InformationBrokerController::class, 'store'])->name('store');
                    Route::get('/{broker}', [InformationBrokerController::class, 'show'])->name('show');
                    Route::patch('/{broker}', [InformationBrokerController::class, 'update'])->name('update');
                    Route::delete('/{broker}', [InformationBrokerController::class, 'destroy'])->name('destroy');
                });

            /**
             * الصيادين والبحارة. A moderator reads the submissions filed under the ports it
             * holds; ruling on one stays with the desk, so the review route asks for a
             * section no moderator level answers for.
             */
            Route::middleware('information.scope:'.InformationScope::SUBMISSIONS)->group(function (): void {
                Route::get('/', [InformationAdminController::class, 'index'])->name('index');
                Route::get('/{submission}', [InformationAdminController::class, 'show'])->name('show');
                Route::get('/{submission}/documents/{category}', [InformationAdminController::class, 'document'])->name('documents.show');
            });

            Route::patch('/{submission}/review', [InformationAdminController::class, 'review'])
                ->middleware('information.scope:'.InformationScope::REVIEW)
                ->name('review');
        });
};

$informationDomain = config('information.domain');

if (is_string($informationDomain) && $informationDomain !== '') {
    Route::domain($informationDomain)->name('information.')->group($informationPortal);
} else {
    Route::prefix('info')->name('information.')->group($informationPortal);
}
