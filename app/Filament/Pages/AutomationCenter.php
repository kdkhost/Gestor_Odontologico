<?php

namespace App\Filament\Pages;

use App\Services\OperationalAutomationService;
use App\Services\SettingService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class AutomationCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Automação';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Central de automação';

    protected string $view = 'filament.pages.automation-center';

    public array $state = [];

    public array $lastResults = [];

    public array $logs = [];

    public function mount(SettingService $settings, OperationalAutomationService $automation): void
    {
        $this->state = [
            'appointment_reminder_enabled' => $settings->get('automation', 'appointment_reminder_enabled', config('clinic.automation.appointment_reminder_enabled')),
            'appointment_reminder_hours_before' => $settings->get('automation', 'appointment_reminder_hours_before', config('clinic.automation.appointment_reminder_hours_before')),
            'financial_due_enabled' => $settings->get('automation', 'financial_due_enabled', config('clinic.automation.financial_due_enabled')),
            'financial_due_days_before' => $settings->get('automation', 'financial_due_days_before', config('clinic.automation.financial_due_days_before')),
            'patient_reactivation_enabled' => $settings->get('automation', 'patient_reactivation_enabled', config('clinic.automation.patient_reactivation_enabled')),
            'patient_reactivation_after_days' => $settings->get('automation', 'patient_reactivation_after_days', config('clinic.automation.patient_reactivation_after_days')),
            'reactivation_cooldown_days' => $settings->get('automation', 'reactivation_cooldown_days', config('clinic.automation.reactivation_cooldown_days')),
        ];

        $this->refreshLogs($automation);
    }

    public function save(SettingService $settings): void
    {
        $data = Validator::make($this->state, [
            'appointment_reminder_enabled' => ['boolean'],
            'appointment_reminder_hours_before' => ['required', 'integer', 'min:1', 'max:168'],
            'financial_due_enabled' => ['boolean'],
            'financial_due_days_before' => ['required', 'integer', 'min:0', 'max:30'],
            'patient_reactivation_enabled' => ['boolean'],
            'patient_reactivation_after_days' => ['required', 'integer', 'min:30', 'max:365'],
            'reactivation_cooldown_days' => ['required', 'integer', 'min:1', 'max:180'],
        ])->validate();

        foreach ($data as $key => $value) {
            $settings->put(
                'automation',
                $key,
                $value,
                is_bool($value) ? 'boolean' : 'integer',
            );
        }

        Notification::make()
            ->title('Regras de automação salvas.')
            ->success()
            ->send();
    }

    public function runPreview(OperationalAutomationService $automation): void
    {
        $this->lastResults = $automation->runAll(true);
        $this->refreshLogs($automation);

        Notification::make()
            ->title('Prévia da automação executada.')
            ->success()
            ->send();
    }

    public function runNow(OperationalAutomationService $automation): void
    {
        $this->lastResults = $automation->runAll(false);
        $this->refreshLogs($automation);

        Notification::make()
            ->title('Automação executada agora.')
            ->success()
            ->send();
    }

    private function refreshLogs(OperationalAutomationService $automation): void
    {
        $this->logs = $automation->latestLogs()
            ->map(fn ($log) => $log->toArray())
            ->all();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('notificacoes.manage') === true;
    }
}
