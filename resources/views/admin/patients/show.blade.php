@extends('admin.layouts.panel')

@php($pageTitle = 'Perfil 360')
@php($summary = $snapshot['summary'])
@php($identity = $snapshot['identity'])
@php($attention = $snapshot['attention'])

@section('content_header')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center admin-flex-gap">
        <div>
            <h1 class="m-0 text-dark">{{ $identity['name'] }}</h1>
            <p class="text-muted mb-0">Perfil 360 do paciente com leitura clinica, operacional e financeira.</p>
        </div>
        <div class="d-flex flex-wrap admin-flex-gap-sm">
            <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Voltar para pacientes
            </a>
            <a href="{{ $coreProfileUrl }}" target="_blank" rel="noreferrer" class="btn btn-primary">
                <i class="fas fa-up-right-from-square mr-1"></i>Abrir perfil legado
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-{{ $attention['level'] === 'critico' ? 'danger' : ($attention['level'] === 'alerta' ? 'warning' : 'success') }} admin-kpi-box">
                <div class="inner">
                    <h3>{{ $attention['label'] }}</h3>
                    <p>Nivel de atencao</p>
                </div>
                <div class="icon"><i class="fas fa-user-shield"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary admin-kpi-box">
                <div class="inner">
                    <h3>R$ {{ number_format((float) $summary['open_balance'], 2, ',', '.') }}</h3>
                    <p>Saldo em aberto</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning admin-kpi-box">
                <div class="inner">
                    <h3>R$ {{ number_format((float) $summary['overdue_balance'], 2, ',', '.') }}</h3>
                    <p>Saldo vencido</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info admin-kpi-box">
                <div class="inner">
                    <h3>{{ $summary['pending_documents_count'] }}</h3>
                    <p>Documentos pendentes</p>
                </div>
                <div class="icon"><i class="fas fa-file-signature"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Identificacao e contato</h3>
                </div>
                <div class="card-body">
                    <dl class="admin-definition-list">
                        <dt>Nome preferido</dt>
                        <dd>{{ $identity['preferred_name'] ?: 'Nao informado' }}</dd>
                        <dt>CPF</dt>
                        <dd>{{ $identity['cpf'] ?: 'Nao informado' }}</dd>
                        <dt>Nascimento</dt>
                        <dd>{{ $identity['birth_date'] ?: 'Nao informado' }}</dd>
                        <dt>Idade</dt>
                        <dd>{{ $identity['age'] ?: '-' }}</dd>
                        <dt>Celular</dt>
                        <dd>{{ $identity['phone'] ?: 'Nao informado' }}</dd>
                        <dt>WhatsApp</dt>
                        <dd>{{ $identity['whatsapp'] ?: 'Nao informado' }}</dd>
                        <dt>E-mail</dt>
                        <dd>{{ $identity['email'] ?: 'Nao informado' }}</dd>
                        <dt>Profissao</dt>
                        <dd>{{ $identity['occupation'] ?: 'Nao informado' }}</dd>
                        <dt>Unidade</dt>
                        <dd>{{ $identity['unit_name'] ?: 'Nao vinculada' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Pontos de atencao</h3>
                </div>
                <div class="card-body">
                    <ul class="admin-bullet-list mb-4">
                        @forelse ($attention['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @empty
                            <li>Nenhum alerta critico ou operacional no momento.</li>
                        @endforelse
                    </ul>

                    <div class="admin-mini-panel">
                        <span>Ultima visita</span>
                        <strong>{{ optional($identity['last_visit_at'])->format('d/m/Y H:i') ?: 'Sem historico' }}</strong>
                    </div>
                    <div class="admin-mini-panel">
                        <span>Proximo agendamento</span>
                        <strong>
                            @if ($summary['next_appointment'])
                                {{ optional($summary['next_appointment']->scheduled_start)->format('d/m/Y H:i') }}
                            @else
                                Sem agenda futura
                            @endif
                        </strong>
                    </div>
                    <div class="admin-mini-panel">
                        <span>Proxima parcela</span>
                        <strong>
                            @if ($summary['next_installment'])
                                {{ optional($summary['next_installment']->due_date)->format('d/m/Y') }}
                            @else
                                Sem parcelas em aberto
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Resumo assistencial</h3>
                </div>
                <div class="card-body">
                    <div class="admin-metric-chip mb-3">
                        <span>Planos ativos</span>
                        <strong>{{ $summary['active_treatment_plans'] }}</strong>
                    </div>
                    <div class="admin-metric-chip mb-3">
                        <span>No-show em 180 dias</span>
                        <strong>{{ $summary['no_show_count'] }}</strong>
                    </div>
                    <div class="admin-metric-chip">
                        <span>Ultimo atendimento registrado</span>
                        <strong>
                            @if ($summary['last_appointment'])
                                {{ optional($summary['last_appointment']->scheduled_start)->format('d/m/Y H:i') }}
                            @else
                                Sem historico
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Agenda recente e futura</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover admin-table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Procedimento</th>
                                    <th>Profissional</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snapshot['recent_appointments'] as $appointment)
                                    <tr>
                                        <td>{{ optional($appointment->scheduled_start)->format('d/m/Y H:i') }}</td>
                                        <td>{{ $appointment->procedure?->name ?? '-' }}</td>
                                        <td>{{ $appointment->professional?->user?->name ?? '-' }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $appointment->status ?? '-')) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Sem agendamentos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Financeiro em aberto</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover admin-table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Descricao</th>
                                    <th>Vencimento</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snapshot['open_receivables'] as $receivable)
                                    <tr>
                                        <td>{{ $receivable->description }}</td>
                                        <td>{{ optional($receivable->due_date)->format('d/m/Y') }}</td>
                                        <td>R$ {{ number_format((float) $receivable->net_amount, 2, ',', '.') }}</td>
                                        <td>{{ ucfirst($receivable->status) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Sem contas pendentes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Planos de tratamento</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover admin-table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Itens</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snapshot['treatment_plans'] as $plan)
                                    <tr>
                                        <td>{{ $plan->name }}</td>
                                        <td>{{ ucfirst($plan->status) }}</td>
                                        <td>{{ $plan->items_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Sem planos vinculados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Aceites documentais</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover admin-table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Aceito em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snapshot['document_acceptances'] as $acceptance)
                                    <tr>
                                        <td>{{ $acceptance->documentTemplate?->name ?? 'Documento' }}</td>
                                        <td>{{ optional($acceptance->accepted_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">Sem documentos aceitos ainda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
