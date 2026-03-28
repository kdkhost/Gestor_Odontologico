<?php

use App\Http\Controllers\AdminCalendarFeedController;
use App\Http\Controllers\AdminInsuranceAuthorizationExportController;
use App\Http\Controllers\AdminInsuranceClaimBatchExportController;
use App\Http\Controllers\AdminPrivacyExportController;
use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\BusinessIntelligenceExportController;
use App\Http\Controllers\EvolutionWebhookController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\PortalDashboardController;
use App\Http\Controllers\PortalDocumentController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ViaCepController;
use App\Services\InstallerService;
use Illuminate\Support\Facades\Route;

Route::get('/instalar', [InstallController::class, 'index'])->name('install.index');
Route::post('/instalar', [InstallController::class, 'store'])->name('install.store');

Route::get('/pwa/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/pwa/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.service-worker');
Route::get('/viacep/{cep}', [ViaCepController::class, 'show'])->name('viacep.show');
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)->name('webhooks.mercadopago');
Route::post('/webhooks/evolution', EvolutionWebhookController::class)->name('webhooks.evolution');
Route::get('/admin/agenda/feed', AdminCalendarFeedController::class)
    ->middleware(['auth', 'scheduled.access'])
    ->name('admin.calendar.feed');
Route::get('/admin/bi/export/{section}', BusinessIntelligenceExportController::class)
    ->middleware(['auth', 'scheduled.access'])
    ->name('admin.bi.export');
Route::get('/admin/lgpd/exports/{request}', AdminPrivacyExportController::class)
    ->middleware(['auth', 'scheduled.access'])
    ->name('admin.privacy-exports.download');
Route::get('/admin/insurance-authorizations/{authorization}/export', AdminInsuranceAuthorizationExportController::class)
    ->middleware(['auth', 'scheduled.access'])
    ->name('admin.insurance-authorizations.export');
Route::get('/admin/insurance-claims/{batch}/export', AdminInsuranceClaimBatchExportController::class)
    ->middleware(['auth', 'scheduled.access'])
    ->name('admin.insurance-claims.export');

Route::get('/', function (InstallerService $installer) {
    if (! $installer->isInstalled()) {
        return redirect()->route('install.index');
    }

    return app(LandingController::class)->index();
})->name('home');
Route::post('/agendamento', [AppointmentRequestController::class, 'store'])->name('appointments.request');

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [PortalAuthController::class, 'login'])->name('login.attempt');
        Route::get('/registrar', [PortalAuthController::class, 'showRegister'])->name('register');
        Route::post('/registrar', [PortalAuthController::class, 'register'])->name('register.store');
    });

    Route::middleware(['auth', 'scheduled.access'])->group(function () {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::post('/documentos/{template}/aceitar', [PortalDocumentController::class, 'accept'])->name('documents.accept');
        Route::post('/pwa/subscriptions', [PwaController::class, 'storeSubscription'])->name('pwa.subscribe');
    });
});
