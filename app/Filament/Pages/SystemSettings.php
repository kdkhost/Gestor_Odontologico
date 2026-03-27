<?php

namespace App\Filament\Pages;

use App\Services\SettingService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;

class SystemSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configurações do sistema';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $title = 'Configurações do sistema';

    protected string $view = 'filament.pages.system-settings';

    public array $state = [];

    public function mount(SettingService $settings): void
    {
        $this->state = [
            'system_version' => $settings->get('branding', 'system_version', config('clinic.system_version')),
            'developer_name' => $settings->get('developer', 'name', config('clinic.developer.name')),
            'developer_email' => $settings->get('developer', 'email', config('clinic.developer.email')),
            'developer_whatsapp' => $settings->get('developer', 'whatsapp', config('clinic.developer.whatsapp')),
            'developer_site' => $settings->get('developer', 'site', config('clinic.developer.site')),
            'developer_footer_note' => $settings->get('developer', 'footer_note', config('clinic.developer.footer_note')),
            'tour_auto_start' => $settings->get('onboarding', 'auto_start', config('clinic.onboarding.auto_start')),
            'whatsapp_dispatch_enabled' => $settings->get('whatsapp', 'dispatch_enabled', config('clinic.whatsapp.dispatch_enabled')),
            'whatsapp_respect_business_hours' => $settings->get('whatsapp', 'respect_business_hours', config('clinic.whatsapp.respect_business_hours')),
            'whatsapp_business_hours_start' => $settings->get('whatsapp', 'business_hours_start', config('clinic.whatsapp.business_hours_start')),
            'whatsapp_business_hours_end' => $settings->get('whatsapp', 'business_hours_end', config('clinic.whatsapp.business_hours_end')),
            'whatsapp_min_interval_seconds' => $settings->get('whatsapp', 'min_interval_seconds', config('clinic.whatsapp.min_interval_seconds')),
            'whatsapp_max_per_minute' => $settings->get('whatsapp', 'max_per_minute', config('clinic.whatsapp.max_per_minute')),
            'whatsapp_max_per_hour_per_recipient' => $settings->get('whatsapp', 'max_per_hour_per_recipient', config('clinic.whatsapp.max_per_hour_per_recipient')),
            'whatsapp_require_opt_in' => $settings->get('whatsapp', 'require_opt_in', config('clinic.whatsapp.require_opt_in')),
            'whatsapp_signature' => $settings->get('whatsapp', 'signature', config('clinic.whatsapp.signature')),
            'whatsapp_default_delay_ms' => $settings->get('whatsapp', 'default_delay_ms', config('clinic.whatsapp.default_delay_ms')),
            'whatsapp_link_preview' => $settings->get('whatsapp', 'link_preview', config('clinic.whatsapp.link_preview')),
        ];
    }

    public function save(SettingService $settings): void
    {
        $data = Validator::make($this->state, [
            'system_version' => ['required', 'string', 'max:20'],
            'developer_name' => ['nullable', 'string', 'max:120'],
            'developer_email' => ['nullable', 'email', 'max:120'],
            'developer_whatsapp' => ['nullable', 'string', 'max:20'],
            'developer_site' => ['nullable', 'url', 'max:255'],
            'developer_footer_note' => ['nullable', 'string', 'max:180'],
            'tour_auto_start' => ['boolean'],
            'whatsapp_dispatch_enabled' => ['boolean'],
            'whatsapp_respect_business_hours' => ['boolean'],
            'whatsapp_business_hours_start' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'whatsapp_business_hours_end' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'whatsapp_min_interval_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'whatsapp_max_per_minute' => ['required', 'integer', 'min:1', 'max:120'],
            'whatsapp_max_per_hour_per_recipient' => ['required', 'integer', 'min:1', 'max:120'],
            'whatsapp_require_opt_in' => ['boolean'],
            'whatsapp_signature' => ['nullable', 'string', 'max:500'],
            'whatsapp_default_delay_ms' => ['required', 'integer', 'min:0', 'max:15000'],
            'whatsapp_link_preview' => ['boolean'],
        ])->validate();

        $settings->put('branding', 'system_version', $data['system_version']);
        $settings->put('developer', 'name', $data['developer_name'] ?? '');
        $settings->put('developer', 'email', $data['developer_email'] ?? '');
        $settings->put('developer', 'whatsapp', $data['developer_whatsapp'] ?? '');
        $settings->put('developer', 'site', $data['developer_site'] ?? '');
        $settings->put('developer', 'footer_note', $data['developer_footer_note'] ?? '');
        $settings->put('onboarding', 'auto_start', $data['tour_auto_start'] ?? false, 'boolean');
        $settings->put('whatsapp', 'dispatch_enabled', $data['whatsapp_dispatch_enabled'] ?? false, 'boolean');
        $settings->put('whatsapp', 'respect_business_hours', $data['whatsapp_respect_business_hours'] ?? true, 'boolean');
        $settings->put('whatsapp', 'business_hours_start', $data['whatsapp_business_hours_start']);
        $settings->put('whatsapp', 'business_hours_end', $data['whatsapp_business_hours_end']);
        $settings->put('whatsapp', 'min_interval_seconds', $data['whatsapp_min_interval_seconds'], 'integer');
        $settings->put('whatsapp', 'max_per_minute', $data['whatsapp_max_per_minute'], 'integer');
        $settings->put('whatsapp', 'max_per_hour_per_recipient', $data['whatsapp_max_per_hour_per_recipient'], 'integer');
        $settings->put('whatsapp', 'require_opt_in', $data['whatsapp_require_opt_in'] ?? true, 'boolean');
        $settings->put('whatsapp', 'signature', $data['whatsapp_signature'] ?? '');
        $settings->put('whatsapp', 'default_delay_ms', $data['whatsapp_default_delay_ms'], 'integer');
        $settings->put('whatsapp', 'link_preview', $data['whatsapp_link_preview'] ?? false, 'boolean');

        Notification::make()
            ->title('Configurações salvas com sucesso.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('configuracoes.manage') === true;
    }
}
