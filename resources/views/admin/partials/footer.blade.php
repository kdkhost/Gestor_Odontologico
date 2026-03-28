@php
    $settings = app(\App\Services\SettingService::class);
    $developerName = $settings->get('developer', 'name', config('clinic.developer.name'));
    $developerEmail = $settings->get('developer', 'email', config('clinic.developer.email'));
    $developerWhatsApp = $settings->get('developer', 'whatsapp', config('clinic.developer.whatsapp'));
    $developerSite = $settings->get('developer', 'site', config('clinic.developer.site'));
    $footerNote = $settings->get('developer', 'footer_note', config('clinic.developer.footer_note'));
    $systemVersion = $settings->get('branding', 'system_version', config('clinic.system_version'));
@endphp

<div class="admin-footer d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap-sm">
    <div>
        <strong>{{ config('app.name') }}</strong>
        <span class="text-muted">versao {{ $systemVersion }} | PHP {{ PHP_VERSION }}</span>
    </div>
    <div class="text-muted">
        <span>Desenvolvedor: {{ $developerName ?: 'Nao configurado' }}</span>
        @if ($developerEmail)
            <span> | {{ $developerEmail }}</span>
        @endif
        @if ($developerWhatsApp)
            <span> | {{ $developerWhatsApp }}</span>
        @endif
        @if ($developerSite)
            <span> | <a href="{{ $developerSite }}" target="_blank" rel="noreferrer">{{ $developerSite }}</a></span>
        @endif
        @if ($footerNote)
            <span> | {{ $footerNote }}</span>
        @endif
    </div>
</div>
