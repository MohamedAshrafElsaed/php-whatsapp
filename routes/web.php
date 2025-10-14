<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\WaSessionController;
use App\Http\Controllers\ContactController;
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
    Route::post('/session/pairing', [WaSessionController::class, 'storePairing'])->name('session.pairing');
    Route::get('/session/status', [WaSessionController::class, 'status'])->name('session.status');
    Route::post('/session/refresh', [WaSessionController::class, 'refresh'])->name('session.refresh');
    Route::delete('/session', [WaSessionController::class, 'destroy'])->name('session.destroy');

    // NEW: Callback endpoints for Node.js (no auth middleware, uses token verification)
    Route::post('/session/credentials/store', [WaSessionController::class, 'storeCredentials'])
        ->name('session.credentials.store')
        ->withoutMiddleware(['auth']);

    Route::post('/session/credentials/load', [WaSessionController::class, 'loadCredentials'])
        ->name('session.credentials.load')
        ->withoutMiddleware(['auth']);
});

// Contacts Import Routes
Route::middleware(['auth'])->prefix('contacts')->name('imports.')->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('index');
    Route::get('/imports/template', [ImportController::class, 'template'])->name('template');
    Route::post('/imports', [ImportController::class, 'store'])->name('store');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('show');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('destroy');
});

// CRM Contact Routes (Separate from imports)
Route::middleware(['auth'])->prefix('contacts')->name('contacts.')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('index');
    Route::get('/create', [ContactController::class, 'create'])->name('create');
    Route::post('/', [ContactController::class, 'store'])->name('store');
    Route::get('/{recipient}', [ContactController::class, 'show'])->name('show');
    Route::post('/{recipient}/send', [ContactController::class, 'sendMessage'])->name('send');
    Route::delete('/{recipient}', [ContactController::class, 'destroy'])->name('destroy');
});

// Campaign Routes
Route::middleware(['auth'])->prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::get('/create', [CampaignController::class, 'create'])->name('create');
    Route::post('/', [CampaignController::class, 'store'])->name('store');
    Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
    Route::post('/{campaign}/start', [CampaignController::class, 'start'])->name('start');
    Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause');
    Route::post('/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('cancel');
    Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
});

// Feature Request Routes
Route::middleware(['auth'])->prefix('feature-requests')->name('feature-requests.')->group(function () {
    Route::get('/', [\App\Http\Controllers\FeatureRequestController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\FeatureRequestController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\FeatureRequestController::class, 'store'])->name('store');
    Route::get('/{featureRequest}', [\App\Http\Controllers\FeatureRequestController::class, 'show'])->name('show');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
