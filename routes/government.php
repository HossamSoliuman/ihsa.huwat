<?php

use App\Http\Controllers\Government\Auth\LoginController;
use App\Http\Controllers\Government\DashboardController;
use App\Http\Controllers\Government\SeasonController;
use Illuminate\Support\Facades\Route;

Route::prefix('gov')->name('government.')->group(function (): void {
    Route::middleware('guest:government')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware('throttle:government-login')
            ->name('login.store');
    });

    Route::middleware(['auth:government', 'government.admin'])->group(function (): void {
        Route::redirect('/', '/gov/dashboard')->name('index');
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/seasons', [SeasonController::class, 'index'])->name('seasons.index');
        Route::get('/seasons/create', [SeasonController::class, 'create'])->name('seasons.create');
        Route::post('/seasons', [SeasonController::class, 'store'])->name('seasons.store');
    });
});
