@php
    $snapshot = $getState();
    $attention = $snapshot['attention'];
    $attentionClasses = match ($attention['level']) {
        'critico' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300',
        'alerta' => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300',
        default => 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300',
    };
@endphp

<div class="space-y-6">
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['identity']['name'] }}</h2>
                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] {{ $attentionClasses }}">
                        {{ $attention['label'] }}
                    </span>
                </div>

                <div class="grid gap-2 text-sm text-gray-500 dark:text-gray-400 md:grid-cols-2 xl:grid-cols-3">
                    <div>Nome preferido: {{ $snapshot['identity']['preferred_name'] ?: 'Não informado' }}</div>
                    <div>CPF: {{ $snapshot['identity']['cpf'] ?: 'Não informado' }}</div>
                    <div>Idade: {{ $snapshot['identity']['age'] ? $snapshot['identity']['age'].' anos' : 'Não informada' }}</div>
                    <div>Celular: {{ $snapshot['identity']['phone'] ?: 'Não informado' }}</div>
                    <div>WhatsApp: {{ $snapshot['identity']['whatsapp'] ?: 'Não informado' }}</div>
                    <div>E-mail: {{ $snapshot['identity']['email'] ?: 'Não informado' }}</div>
                    <div>Profissão: {{ $snapshot['identity']['occupation'] ?: 'Não informada' }}</div>
                    <div>Unidade: {{ $snapshot['identity']['unit_name'] ?: 'Não vinculada' }}</div>
                    <div>Última visita: {{ optional($snapshot['identity']['last_visit_at'])->format('d/m/Y H:i') ?: 'Sem histórico' }}</div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 lg:max-w-md">
                <div class="font-semibold text-gray-950 dark:text-white">Leitura gerencial do paciente</div>
                <div class="mt-2 space-y-2">
                    @forelse ($attention['reasons'] as $reason)
                        <div>{{ $reason }}</div>
                    @empty
                        <div>Paciente sem alertas relevantes no momento.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="text-sm text-gray-500 dark:text-gray-400">Saldo em aberto</div>
            <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) $snapshot['summary']['open_balance'], 2, ',', '.') }}</div>
        </article>
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="text-sm text-gray-500 dark:text-gray-400">Saldo vencido</div>
            <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) $snapshot['summary']['overdue_balance'], 2, ',', '.') }}</div>
        </article>
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="text-sm text-gray-500 dark:text-gray-400">No-show 180 dias</div>
            <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['summary']['no_show_count'] }}</div>
        </article>
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="text-sm text-gray-500 dark:text-gray-400">Documentos pendentes</div>
            <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['summary']['pending_documents_count'] }}</div>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Agenda futura</h3>
            <div class="mt-4 space-y-3">
                @forelse ($snapshot['upcoming_appointments'] as $appointment)
                    <div class="rounded-xl border border-sky-200/70 bg-sky-50/70 p-3 dark:border-sky-900/60 dark:bg-sky-950/40">
                        <div class="font-medium text-gray-950 dark:text-white">{{ optional($appointment->scheduled_start)->format('d/m/Y H:i') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->procedure?->name ?? 'Procedimento não definido' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->professional?->user?->name ?? 'Profissional não definido' }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum agendamento futuro ativo.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Financeiro imediato</h3>
            <div class="mt-4 space-y-3">
                @if ($snapshot['summary']['next_installment'])
                    <div class="rounded-xl border border-amber-200/70 bg-amber-50/70 p-3 dark:border-amber-900/60 dark:bg-amber-950/40">
                        <div class="font-medium text-gray-950 dark:text-white">Próxima parcela</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Vencimento: {{ optional($snapshot['summary']['next_installment']->due_date)->format('d/m/Y') ?: '-' }}
                        </div>
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">
                            R$ {{ number_format((float) $snapshot['summary']['next_installment']->amount, 2, ',', '.') }}
                        </div>
                    </div>
                @endif

                @forelse ($snapshot['open_receivables'] as $receivable)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $receivable->description }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Vencimento: {{ optional($receivable->due_date)->format('d/m/Y') ?: '-' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status: {{ ucfirst($receivable->status) }}</div>
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) $receivable->net_amount, 2, ',', '.') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sem títulos em aberto.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Planos e documentos</h3>
            <div class="mt-4 space-y-3">
                @forelse ($snapshot['treatment_plans'] as $plan)
                    <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/70 p-3 dark:border-emerald-900/60 dark:bg-emerald-950/40">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $plan->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($plan->status) }} | {{ $plan->items_count }} itens</div>
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) $plan->final_amount, 2, ',', '.') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum plano recente cadastrado.</p>
                @endforelse

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-2 font-medium text-gray-950 dark:text-white">Aceites digitais recentes</div>
                    <div class="space-y-2">
                        @forelse ($snapshot['document_acceptances'] as $acceptance)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $acceptance->documentTemplate?->name ?? 'Documento' }} em {{ optional($acceptance->accepted_at)->format('d/m/Y H:i') ?: '-' }}
                            </div>
                        @empty
                            <div class="text-sm text-gray-500 dark:text-gray-400">Nenhum aceite registrado ainda.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Histórico de atendimentos</h3>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($snapshot['recent_appointments'] as $appointment)
                <article class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="font-medium text-gray-950 dark:text-white">{{ optional($appointment->scheduled_start)->format('d/m/Y H:i') ?: '-' }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->procedure?->name ?? 'Procedimento não definido' }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->professional?->user?->name ?? 'Profissional não definido' }}</div>
                    <div class="mt-2 inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                    </div>
                </article>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Sem histórico de consultas registrado.</p>
            @endforelse
        </div>
    </section>
</div>
