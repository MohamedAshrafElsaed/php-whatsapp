<?php

use App\Http\Controllers\AutoReplyController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureRequestController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WaSessionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsAppGroupsController;
use App\Http\Controllers\WhatsAppSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Dashboard Route
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// WhatsApp Multi-Device routes
Route::middleware(['auth'])->prefix('w')->name('wa.')->group(function () {
    // Device management
    Route::get('/connect', [WaSessionController::class, 'index'])->name('connect');
    Route::get('/session/status', [WaSessionController::class, 'status'])->name('session.status');

    // Generate QR/Pairing
    Route::post('/session/qr', [WaSessionController::class, 'generateQr'])->name('session.qr');
    Route::post('/session/pairing', [WaSessionController::class, 'generatePairing'])->name('session.pairing');

    // Refresh QR
    Route::post('/session/{deviceId}/refresh-qr', [WaSessionController::class, 'refreshQr'])->name('session.refresh-qr');

    // Device actions
    Route::post('/session/{deviceId}/set-primary', [WaSessionController::class, 'setPrimary'])->name('session.set-primary');
    Route::delete('/session/{deviceId}', [WaSessionController::class, 'destroy'])->name('session.destroy');

    Route::post('/session/{deviceId}/sync-contacts', [WaSessionController::class, 'syncContacts'])->name('session.sync-contacts');

    Route::post('/session/{deviceId}/reconnect', [WaSessionController::class, 'reconnect'])->name('session.reconnect');
    Route::delete('/session/{deviceId}/force', [WaSessionController::class, 'forceDelete'])->name('session.force-delete');
});

// WhatsApp Groups (Coming Soon)
Route::middleware(['auth'])->group(function () {
    Route::get('/whatsapp-groups', [WhatsAppGroupsController::class, 'index'])->name('whatsapp.groups');
});

// Auto Reply (Coming Soon)
Route::middleware(['auth'])->group(function () {
    Route::get('/auto-reply', [AutoReplyController::class, 'index'])->name('whatsapp.auto-reply');
});

// Reports (Coming Soon)
Route::middleware(['auth'])->group(function () {
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
});

// WhatsApp Settings
Route::middleware(['auth'])->prefix('settings/whatsapp')->name('whatsapp.settings.')->group(function () {
    Route::get('/', [WhatsAppSettingsController::class, 'index'])->name('index');
    Route::post('/', [WhatsAppSettingsController::class, 'update'])->name('update');
});

// Contacts Import Routes
Route::middleware(['auth', 'verified.phone'])->prefix('contacts')->name('imports.')->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('index');
    Route::get('/imports/template', [ImportController::class, 'template'])->name('template');
    Route::post('/imports', [ImportController::class, 'store'])->name('store');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('show');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('destroy');
    Route::prefix('segments')->name('segments.')->group(function () {
        Route::get('/', [SegmentController::class, 'index'])->name('index');
        Route::get('/create', [SegmentController::class, 'create'])->name('create');
        Route::post('/', [SegmentController::class, 'store'])->name('store');
        Route::get('/{segment}', [SegmentController::class, 'show'])->name('show');
        Route::get('/{segment}/edit', [SegmentController::class, 'edit'])->name('edit');
        Route::put('/{segment}', [SegmentController::class, 'update'])->name('update');
        Route::delete('/{segment}', [SegmentController::class, 'destroy'])->name('destroy');
    });
});

// CRM Contact Routes
Route::middleware(['auth', 'verified.phone'])->prefix('contacts')->name('contacts.')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('index');
    Route::get('/create', [ContactController::class, 'create'])->name('create');
    Route::post('/', [ContactController::class, 'store'])->name('store');
    Route::get('/{recipient}', [ContactController::class, 'show'])->name('show');

    // Text message
    Route::post('/{recipient}/send', [ContactController::class, 'sendMessage'])->name('send');

    // Media messages
    Route::post('/{recipient}/send-media', [ContactController::class, 'sendMedia'])->name('send.media');

    // Link message
    Route::post('/{recipient}/send-link', [ContactController::class, 'sendLink'])->name('send.link');

    // Location message
    Route::post('/{recipient}/send-location', [ContactController::class, 'sendLocation'])->name('send.location');

    // Contact card
    Route::post('/{recipient}/send-contact', [ContactController::class, 'sendContact'])->name('send.contact');

    // Poll
    Route::post('/{recipient}/send-poll', [ContactController::class, 'sendPoll'])->name('send.poll');

    Route::delete('/{recipient}', [ContactController::class, 'destroy'])->name('destroy');
});

// Campaign Management Routes
Route::middleware(['auth'])->group(function () {
    // Campaign CRUD
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    // Campaign Actions
    Route::post('/campaigns/{campaign}/start', [CampaignController::class, 'start'])->name('campaigns.start');
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
});

// Feature Request Routes
Route::middleware(['auth'])->prefix('feature-requests')->name('feature-requests.')->group(function () {
    Route::get('/', [FeatureRequestController::class, 'index'])->name('index');
    Route::get('/create', [FeatureRequestController::class, 'create'])->name('create');
    Route::post('/', [FeatureRequestController::class, 'store'])->name('store');
    Route::get('/{featureRequest}', [FeatureRequestController::class, 'show'])->name('show');
});

// Webhook Route (no auth middleware)
Route::post('/webhook/whatsapp', [WebhookController::class, 'handle'])->name('webhook.whatsapp');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ar'])) {
        session(['locale' => $locale]);

        // Update user preference if authenticated
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }

    return redirect()->back();
})->name('lang.switch');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
