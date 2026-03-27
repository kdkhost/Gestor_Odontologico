<?php

namespace App\Filament\Widgets;

use App\Services\OperationsInsightService;
use Filament\Widgets\ChartWidget;

class ClinicPerformanceChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Tendência de produção e receita';

    protected ?string $description = 'Cruza volume assistencial com receita recebida para leitura gerencial rápida.';

    public ?string $filter = '15';

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') === true;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 dias',
            '15' => '15 dias',
            '30' => '30 dias',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?: 15);
        $snapshot = app(OperationsInsightService::class)->snapshot(days: $days);

        return [
            'labels' => $snapshot['trends']['labels'],
            'datasets' => [
                [
                    'label' => 'Atendimentos efetivos',
                    'data' => $snapshot['trends']['appointments'],
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.12)',
                    'pointBackgroundColor' => '#0f766e',
                    'pointBorderColor' => '#0f766e',
                    'tension' => 0.35,
                    'fill' => true,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Receita recebida',
                    'data' => $snapshot['trends']['revenue'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.16)',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#f59e0b',
                    'tension' => 0.35,
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Atendimentos',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Receita (R$)',
                    ],
                ],
            ],
        ];
    }
}
