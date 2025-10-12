<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\WaSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Dashboard Route - Now uses controller for statistics
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// WhatsApp Connection Routes
Route::middleware(['auth'])->prefix('wa')->name('wa.')->group(function () {
    Route::get('/connect', [WaSessionController::class, 'index'])->name('connect');
    Route::post('/session', [WaSessionController::class, 'store'])->name('session.store');
    Route::get('/session/status', [WaSessionController::class, 'status'])->name('session.status');
    Route::post('/session/refresh', [WaSessionController::class, 'refresh'])->name('session.refresh');
    Route::delete('/session', [WaSessionController::class, 'destroy'])->name('session.destroy');
});

// Contacts Import Routes
Route::middleware(['auth'])->prefix('contacts')->name('imports.')->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('index');
    Route::get('/imports/template', [ImportController::class, 'template'])->name('template');
    Route::post('/imports', [ImportController::class, 'store'])->name('store');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('show');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
