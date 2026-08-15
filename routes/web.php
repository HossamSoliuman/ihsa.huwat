<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminResourceController;
use App\Http\Controllers\IntegrationSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->name('admin.index');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('{tab}', [AdminController::class, 'show'])->name('tab');

    Route::post('{tab}/{resource}', [AdminResourceController::class, 'store'])->name('resource.store');
    Route::put('{tab}/{resource}/{id}', [AdminResourceController::class, 'update'])->name('resource.update');
    Route::delete('{tab}/{resource}/{id}', [AdminResourceController::class, 'destroy'])->name('resource.destroy');

    Route::put('{tab}/integration/{provider}', [IntegrationSettingController::class, 'update'])->name('integration.update');
});