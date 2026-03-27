@php
    $settings = app(\App\Services\SettingService::class);
    $developerName = $settings->get('developer', 'name', config('clinic.developer.name'));
    $developerEmail = $settings->get('developer', 'email', config('clinic.developer.email'));
    $developerWhatsApp = $settings->get('developer', 'whatsapp', config('clinic.developer.whatsapp'));
    $developerSite = $settings->get('developer', 'site', config('clinic.developer.site'));
    $footerNote = $settings->get('developer', 'footer_note', config('clinic.developer.footer_note'));
    $systemVersion = $settings->get('branding', 'system_version', config('clinic.system_version'));
@endphp

<div class="mx-6 mb-6 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            <div class="font-semibold text-gray-900 dark:text-white">
                {{ $developerName ?: 'Desenvolvedor não configurado' }}
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                @if ($developerEmail)
                    <span>{{ $developerEmail }}</span>
                @endif

                @if ($developerWhatsApp)
                    <span>{{ $developerWhatsApp }}</span>
                @endif

                @if ($developerSite)
                    <a href="{{ $developerSite }}" target="_blank" rel="noreferrer" class="text-primary-600 hover:underline dark:text-primary-400">{{ $developerSite }}</a>
                @endif
            </div>

            @if ($footerNote)
                <div>{{ $footerNote }}</div>
            @endif
        </div>

        <div class="flex flex-col items-start gap-2 md:items-end">
            <div class="font-medium">
                Sistema v{{ $systemVersion }} · PHP {{ PHP_VERSION }}
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    x-data
                    x-on:click="window.dispatchEvent(new CustomEvent('odonto-flow-tour-restart'))"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                >
                    Reiniciar tour
                </button>

                @if (auth()->user()?->can('configuracoes.manage'))
                    <a
                        href="{{ \App\Filament\Pages\SystemSettings::getUrl(panel: 'admin') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                    >
                        Configurações do sistema
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
