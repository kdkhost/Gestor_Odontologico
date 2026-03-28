@extends('admin.layouts.panel')

@php($pageTitle = 'Dashboard')

@section('content_header')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap" data-admin-tour="dashboard-hero">
        <div>
            <h1 class="m-0 text-dark">{{ $greeting }}, {{ auth()->user()->name }}.</h1>
            <p class="text-muted mb-0">
                {{ $systemName }} | versao {{ $systemVersion }} | escopo {{ $snapshot['scope']['label'] }}
            </p>
        </div>
        <div class="d-flex flex-wrap admin-flex-gap-sm">
            <a href="{{ route('admin.workspace', ['slug' => 'agenda-visual']) }}" class="btn btn-primary">
                <i class="far fa-calendar-alt mr-1"></i>Abrir agenda visual
            </a>
            <button type="button" class="btn btn-outline-secondary" data-action="restart-tour">
                <i class="fas fa-route mr-1"></i>Reiniciar tour
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary admin-kpi-box">
                <div class="inner">
                    <h3>{{ $stats['today_appointments'] }}</h3>
                    <p>Consultas previstas hoje</p>
                </div>
                <div class="icon"><i class="far fa-calendar-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info admin-kpi-box">
                <div class="inner">
                    <h3>{{ $stats['today_pending_confirmation'] }}</h3>
                    <p>Confirmacoes pendentes</p>
                </div>
                <div class="icon"><i class="fas fa-phone-volume"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning admin-kpi-box">
                <div class="inner">
                    <h3>R$ {{ number_format((float) $stats['overdue_total'], 2, ',', '.') }}</h3>
                    <p>Inadimplencia vencida</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger admin-kpi-box">
                <div class="inner">
                    <h3>{{ $stats['critical_stock_count'] }}</h3>
                    <p>Itens em estoque critico</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Resumo operacional do dia</h3>
                </div>
                <div class="card-body">
                    <div class="row admin-metric-strip">
                        <div class="col-md-4">
                            <div class="admin-metric-chip">
                                <span>Taxa de confirmacao</span>
                                <strong>{{ number_format((float) $stats['confirmation_rate'], 1, ',', '.') }}%</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-metric-chip">
                                <span>No-show em 30 dias</span>
                                <strong>{{ number_format((float) $stats['no_show_rate'], 1, ',', '.') }}%</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-metric-chip">
                                <span>Lotes vencendo</span>
                                <strong>{{ $stats['expiring_batch_count'] }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="admin-alert-panel">
                                <h4>Agenda imediata</h4>
                                <ul class="list-unstyled mb-0">
                                    @forelse ($appointmentsNeedingConfirmation as $appointment)
                                        <li>
                                            <strong>{{ $appointment->patient?->name ?? 'Paciente' }}</strong>
                                            <span>{{ optional($appointment->scheduled_start)->format('d/m H:i') }}</span>
                                        </li>
                                    @empty
                                        <li class="text-muted">Nenhuma consulta aguardando confirmacao.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-alert-panel">
                                <h4>Financeiro vencido</h4>
                                <ul class="list-unstyled mb-0">
                                    @forelse ($overdueReceivables as $receivable)
                                        <li>
                                            <strong>{{ $receivable->patient?->name ?? 'Paciente' }}</strong>
                                            <span>R$ {{ number_format((float) $receivable->net_amount, 2, ',', '.') }}</span>
                                        </li>
                                    @empty
                                        <li class="text-muted">Sem contas vencidas na amostra.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-alert-panel">
                                <h4>Estoque critico</h4>
                                <ul class="list-unstyled mb-0">
                                    @forelse ($lowStockItems as $item)
                                        <li>
                                            <strong>{{ $item->name }}</strong>
                                            <span>{{ number_format((float) $item->current_stock, 2, ',', '.') }} un.</span>
                                        </li>
                                    @empty
                                        <li class="text-muted">Sem itens criticos agora.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-success" data-admin-tour="system-health">
                <div class="card-header">
                    <h3 class="card-title">Saude do ambiente</h3>
                </div>
                <div class="card-body">
                    <div class="admin-health-score">
                        <div>
                            <span>Requisitos PHP</span>
                            <strong>{{ $healthScore }}/{{ $healthTotal }}</strong>
                        </div>
                        <span class="badge badge-{{ $healthScore === $healthTotal ? 'success' : 'warning' }}">
                            {{ $healthScore === $healthTotal ? 'Saudavel' : 'Ajuste necessario' }}
                        </span>
                    </div>
                    <ul class="list-group list-group-flush mt-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Fila pendente</span>
                            <strong>{{ $health['infrastructure']['queue_jobs_pending'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Falhas de fila</span>
                            <strong>{{ $health['infrastructure']['queue_failed_jobs'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Webhook Mercado Pago</span>
                            <strong>{{ $health['webhooks']['mercadopago']['label'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Webhook Evolution</span>
                            <strong>{{ $health['webhooks']['evolution']['label'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Storage publico</span>
                            <strong>{{ $health['infrastructure']['public_storage_link'] ? 'Ativo' : 'Pendente' }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary" data-admin-tour="module-grid">
        <div class="card-header">
            <h3 class="card-title">Navegacao operacional</h3>
        </div>
        <div class="card-body">
            @foreach ($moduleGroups as $group => $modules)
                <div class="admin-module-group">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="admin-section-title mb-0">{{ $group }}</h4>
                        <span class="badge badge-light">{{ $modules->count() }} modulos</span>
                    </div>
                    <div class="row">
                        @foreach ($modules as $module)
                            @php($moduleRoute = \App\Support\AdminModuleRegistry::routeName($module))
                            @php($moduleRouteParameters = \App\Support\AdminModuleRegistry::routeParameters($module))
                            <div class="col-xl-3 col-md-6">
                                <a href="{{ route($moduleRoute, $moduleRouteParameters) }}" class="admin-module-card admin-module-card-{{ $module['color'] }}">
                                    <span class="admin-module-card__icon"><i class="{{ $module['icon'] }}"></i></span>
                                    <strong>{{ $module['label'] }}</strong>
                                    <p>{{ $module['description'] }}</p>
                                    <span class="admin-module-card__action">Abrir modulo</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@stop
