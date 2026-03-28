<?php

use App\Http\Controllers\Admin\AdminAppointmentIndexController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPatientIndexController;
use App\Http\Controllers\Admin\AdminPatientProfileController;
use App\Http\Controllers\Admin\AdminWorkspaceController;
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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt');

    Route::middleware(['admin.user', 'scheduled.access'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/agenda/feed', AdminCalendarFeedController::class)->name('calendar.feed');
        Route::get('/agenda', AdminAppointmentIndexController::class)->name('appointments.index');
        Route::get('/bi/export/{section}', BusinessIntelligenceExportController::class)->name('bi.export');
        Route::get('/lgpd/exports/{request}', AdminPrivacyExportController::class)->name('privacy-exports.download');
        Route::get('/insurance-authorizations/{authorization}/export', AdminInsuranceAuthorizationExportController::class)->name('insurance-authorizations.export');
        Route::get('/insurance-claims/{batch}/export', AdminInsuranceClaimBatchExportController::class)->name('insurance-claims.export');
        Route::get('/pacientes', AdminPatientIndexController::class)->name('patients.index');
        Route::get('/pacientes/{patient}', AdminPatientProfileController::class)->name('patients.show');
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/modulos/{slug}', AdminWorkspaceController::class)->name('workspace');
    });
});

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
