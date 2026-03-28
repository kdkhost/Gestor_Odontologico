@extends('admin.layouts.panel')

@php($pageTitle = $module['label'])

@section('content_header')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap">
        <div>
            <h1 class="m-0 text-dark">{{ $module['label'] }}</h1>
            <p class="text-muted mb-0">{{ $module['description'] }}</p>
        </div>
        <div class="d-flex flex-wrap admin-flex-gap-sm">
            <a href="{{ route('admin.appointments.index') }}" class="btn btn-primary">
                <i class="fas fa-calendar-check mr-1"></i>Ir para agenda
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
                    <p>Pacientes filtrados</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['active'] }}</h3>
                    <p>Cadastros ativos</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['whatsapp_opt_in'] }}</h3>
                    <p>Opt-in de WhatsApp</p>
                </div>
                <div class="icon"><i class="fab fa-whatsapp"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['reactivation'] }}</h3>
                    <p>Reativacao sugerida</p>
                </div>
                <div class="icon"><i class="fas fa-bullhorn"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary admin-filter-card">
        <div class="card-header">
            <h3 class="card-title">Filtros de pacientes</h3>
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('admin.patients.index') }}" class="admin-filter-form">
                <div class="row">
                    <div class="col-lg-5 col-md-12">
                        <div class="form-group">
                            <label for="q">Busca rapida</label>
                            <input type="text" id="q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nome, nome preferido, CPF, celular ou e-mail">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="form-group">
                            <label for="active">Status</label>
                            <select id="active" name="active" class="form-control">
                                <option value="active" @selected($filters['active'] === 'active')>Ativos</option>
                                <option value="inactive" @selected($filters['active'] === 'inactive')>Inativos</option>
                                <option value="all" @selected($filters['active'] === 'all')>Todos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="form-group">
                            <label for="whatsapp_opt_in">WhatsApp</label>
                            <select id="whatsapp_opt_in" name="whatsapp_opt_in" class="form-control">
                                <option value="all" @selected($filters['whatsapp_opt_in'] === 'all')>Todos</option>
                                <option value="yes" @selected($filters['whatsapp_opt_in'] === 'yes')>Com opt-in</option>
                                <option value="no" @selected($filters['whatsapp_opt_in'] === 'no')>Sem opt-in</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4">
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
                    <div class="col-12 d-flex justify-content-lg-end admin-flex-gap-sm">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i>Aplicar filtros
                        </button>
                        <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-rotate-left mr-1"></i>Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Painel de pacientes</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover admin-table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Contato</th>
                            <th>Unidade</th>
                            <th>Ultima visita</th>
                            <th>Atencao</th>
                            <th class="text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $patient)
                            <tr>
                                <td>
                                    <strong>{{ $patient->name }}</strong>
                                    @if ($patient->preferred_name)
                                        <div class="text-muted small">Nome preferido: {{ $patient->preferred_name }}</div>
                                    @endif
                                    <div class="text-muted small">
                                        {{ $patient->cpf ?: 'CPF nao informado' }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $patient->phone ?: $patient->whatsapp ?: '-' }}</div>
                                    <div class="text-muted small">
                                        {{ $patient->email ?: 'E-mail nao informado' }}
                                    </div>
                                </td>
                                <td>
                                    {{ $patient->unit?->name ?? '-' }}
                                    <div class="text-muted small">
                                        {{ $patient->whatsapp_opt_in ? 'WhatsApp liberado' : 'WhatsApp pendente' }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ optional($patient->last_visit_at)->format('d/m/Y H:i') ?: 'Sem historico' }}</div>
                                    <div class="text-muted small">
                                        {{ $patient->upcoming_appointments_count }} agenda(s) futura(s)
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $patient->attention_color }}">
                                        {{ $patient->attention_label }}
                                    </span>
                                    <div class="text-muted small">
                                        {{ $patient->overdue_receivables_count }} financeiro(s) vencido(s) | {{ $patient->no_show_recent_count }} falta(s)
                                    </div>
                                    @if ($patient->needs_reactivation)
                                        <div class="small text-warning">Reativacao sugerida</div>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.patients.show', ['patient' => $patient->id]) }}" class="btn btn-xs btn-outline-primary">
                                        Perfil 360
                                    </a>
                                    <a href="/admin/core/patients/{{ $patient->id }}/perfil-360" target="_blank" rel="noreferrer" class="btn btn-xs btn-outline-secondary">
                                        Core
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Nenhum paciente encontrado com os filtros atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($patients->hasPages())
            <div class="card-footer clearfix">
                {{ $patients->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@stop
