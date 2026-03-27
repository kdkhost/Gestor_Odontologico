<?php

namespace App\Filament\Widgets;

use App\Services\OperationsInsightService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClinicKpiOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Pulso operacional';

    protected ?string $description = 'Indicadores que exigem leitura rápida da agenda, financeiro e estoque.';

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') === true;
    }

    protected function getStats(): array
    {
        $service = app(OperationsInsightService::class);
        $stats = $service->stats();
        $appointmentTrend = $service->appointmentTrend(7);
        $revenueTrend = $service->revenueTrend(7);

        return [
            Stat::make('Consultas hoje', (string) $stats['today_appointments'])
                ->description("{$stats['today_pending_confirmation']} aguardando confirmação")
                ->descriptionIcon('heroicon-o-clock')
                ->color($stats['today_pending_confirmation'] > 0 ? 'warning' : 'success')
                ->chart($appointmentTrend),
            Stat::make('Confirmação 7 dias', $this->percentage($stats['confirmation_rate']))
                ->description('Quanto maior, menor a ociosidade da recepção')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($stats['confirmation_rate'] >= 80 ? 'success' : 'warning')
                ->chart($appointmentTrend),
            Stat::make('Inadimplência vencida', $this->currency($stats['overdue_total']))
                ->description('Títulos abertos com vencimento já expirado')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($stats['overdue_total'] > 0 ? 'danger' : 'success')
                ->chart($revenueTrend),
            Stat::make('Estoque crítico', (string) $stats['critical_stock_count'])
                ->description("{$stats['expiring_batch_count']} lotes vencendo em 30 dias")
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($stats['critical_stock_count'] > 0 || $stats['expiring_batch_count'] > 0 ? 'warning' : 'success')
                ->chart(array_map(static fn (float|int $value) => (float) $value, $revenueTrend)),
            Stat::make('No-show 30 dias', $this->percentage($stats['no_show_rate']))
                ->description('Pacientes faltosos que merecem recuperação ativa')
                ->descriptionIcon('heroicon-o-user-minus')
                ->color($stats['no_show_rate'] >= 10 ? 'danger' : 'success')
                ->chart($appointmentTrend),
        ];
    }

    private function currency(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function percentage(float $value): string
    {
        return number_format($value, 1, ',', '.').'%';
    }
}
