@extends('admin.layouts.panel')

@php($pageTitle = $module['label'])

@section('content_header')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap">
        <div>
            <h1 class="m-0 text-dark">{{ $module['label'] }}</h1>
            <p class="text-muted mb-0">{{ $module['description'] }}</p>
        </div>
        <div class="d-flex flex-wrap admin-flex-gap-sm">
            <a href="{{ route('admin.workspace', ['slug' => 'agenda-visual']) }}" class="btn btn-primary">
                <i class="far fa-calendar-alt mr-1"></i>Abrir agenda visual
            </a>
            <a href="{{ $module['iframe_url'] }}" target="_blank" rel="noreferrer" class="btn btn-outline-secondary">
                <i class="fas fa-up-right-from-square mr-1"></i>Abrir modulo legado
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['total'] }}</h3>
                    <p>Atendimentos filtrados</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['requested'] }}</h3>
                    <p>Solicitacoes pendentes</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['active'] }}</h3>
                    <p>Fluxo assistencial ativo</p>
                </div>
                <div class="icon"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['issues'] }}</h3>
                    <p>Cancelamentos ou faltas</p>
                </div>
                <div class="icon"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary admin-filter-card">
        <div class="card-header">
            <h3 class="card-title">Filtros de agenda</h3>
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('admin.appointments.index') }}" class="admin-filter-form">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="date_from">Data inicial</label>
                            <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="date_to">Data final</label>
                            <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label for="unit_id">Unidade</label>
                            <select id="unit_id" name="unit_id" class="form-control" @disabled($lockedUnitId)>
                                <option value="">Todas</option>
                                @foreach ($units as $unitId => $unitName)
                                    <option value="{{ $unitId }}" @selected((int) $filters['unit_id'] === (int) $unitId)>{{ $unitName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label for="professional_id">Profissional</label>
                            <select id="professional_id" name="professional_id" class="form-control">
                                <option value="">Todos</option>
                                @foreach ($professionals as $professionalId => $professionalName)
                                    <option value="{{ $professionalId }}" @selected((int) $filters['professional_id'] === (int) $professionalId)>{{ $professionalName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-12">
                        <div class="form-group mb-md-0">
                            <label for="q">Busca rapida</label>
                            <input type="text" id="q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Paciente, procedimento, cadeira, unidade ou profissional">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 d-flex align-items-end justify-content-lg-end admin-flex-gap-sm">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i>Aplicar filtros
                        </button>
                        <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-rotate-left mr-1"></i>Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Agenda operacional</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover admin-table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Horario</th>
                            <th>Paciente</th>
                            <th>Profissional</th>
                            <th>Procedimento</th>
                            <th>Unidade</th>
                            <th>Status</th>
                            <th class="text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            @php($badge = $statusPalette[$appointment->status] ?? 'secondary')
                            <tr>
                                <td>
                                    <strong>{{ optional($appointment->scheduled_start)->format('d/m/Y') }}</strong>
                                    <div class="text-muted small">
                                        {{ optional($appointment->scheduled_start)->format('H:i') }} - {{ optional($appointment->scheduled_end)->format('H:i') }}
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $appointment->patient?->name ?? 'Paciente nao identificado' }}</strong>
                                    <div class="text-muted small">
                                        Origem {{ strtoupper($appointment->origin ?? 'admin') }}
                                        @if ($appointment->chair?->name)
                                            | {{ $appointment->chair->name }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $appointment->professional?->user?->name ?? '-' }}</td>
                                <td>
                                    {{ $appointment->procedure?->name ?? '-' }}
                                    @if ($appointment->notes)
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($appointment->notes, 70) }}</div>
                                    @endif
                                </td>
                                <td>{{ $appointment->unit?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $badge }}">
                                        {{ $statusOptions[$appointment->status] ?? ucfirst($appointment->status ?? '-') }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    @if ($appointment->patient_id)
                                        <a href="{{ route('admin.patients.show', ['patient' => $appointment->patient_id]) }}" class="btn btn-xs btn-outline-primary">
                                            Perfil 360
                                        </a>
                                    @endif
                                    <a href="{{ $module['iframe_url'] }}" target="_blank" rel="noreferrer" class="btn btn-xs btn-outline-secondary">
                                        Core
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhum agendamento encontrado com os filtros atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($appointments->hasPages())
            <div class="card-footer clearfix">
                {{ $appointments->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@stop
