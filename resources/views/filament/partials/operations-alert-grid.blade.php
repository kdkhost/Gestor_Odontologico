@props([
    'snapshot',
    'links' => [],
    'compact' => false,
])

@php
    $cardClasses = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950';
    $muted = 'text-sm text-gray-500 dark:text-gray-400';
    $title = 'text-sm font-semibold text-gray-950 dark:text-white';
@endphp

<div class="grid gap-4 {{ $compact ? 'xl:grid-cols-2' : 'xl:grid-cols-3' }}">
    <section class="{{ $cardClasses }}">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="{{ $title }}">Consultas sem confirmação</h3>
                <p class="{{ $muted }}">Próximas 24 horas com status ainda pendente.</p>
            </div>
            <a href="{{ $links['appointments'] ?? '#' }}" class="text-sm font-medium text-amber-600 hover:text-amber-500">Abrir agenda</a>
        </div>

        <div class="space-y-3">
            @forelse ($snapshot['alerts']['appointments_needing_confirmation'] as $appointment)
                <article class="rounded-xl border border-amber-200/70 bg-amber-50/70 p-3 dark:border-amber-900/60 dark:bg-amber-950/40">
                    <div class="font-medium text-gray-950 dark:text-white">{{ $appointment->patient?->name ?? 'Paciente sem nome' }}</div>
                    <div class="{{ $muted }}">{{ optional($appointment->scheduled_start)->format('d/m/Y H:i') }} | {{ $appointment->unit?->name ?? 'Sem unidade' }}</div>
                    <div class="{{ $muted }}">{{ $appointment->professional?->user?->name ?? 'Profissional não definido' }}</div>
                </article>
            @empty
                <p class="{{ $muted }}">Nenhuma consulta pendente de confirmação no horizonte imediato.</p>
            @endforelse
        </div>
    </section>

    <section class="{{ $cardClasses }}">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="{{ $title }}">Financeiro crítico</h3>
                <p class="{{ $muted }}">Cobranças vencidas com maior urgência operacional.</p>
            </div>
            <a href="{{ $links['receivables'] ?? '#' }}" class="text-sm font-medium text-amber-600 hover:text-amber-500">Abrir financeiro</a>
        </div>

        <div class="space-y-3">
            @forelse ($snapshot['alerts']['overdue_receivables'] as $receivable)
                <article class="rounded-xl border border-rose-200/70 bg-rose-50/70 p-3 dark:border-rose-900/60 dark:bg-rose-950/40">
                    <div class="font-medium text-gray-950 dark:text-white">{{ $receivable->patient?->name ?? 'Paciente sem nome' }}</div>
                    <div class="{{ $muted }}">Vencimento: {{ optional($receivable->due_date)->format('d/m/Y') ?? '-' }}</div>
                    <div class="text-sm font-semibold text-rose-700 dark:text-rose-300">R$ {{ number_format((float) $receivable->net_amount, 2, ',', '.') }}</div>
                </article>
            @empty
                <p class="{{ $muted }}">Nenhum título vencido encontrado no escopo atual.</p>
            @endforelse
        </div>
    </section>

    <section class="{{ $cardClasses }}">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="{{ $title }}">Estoque sob atenção</h3>
                <p class="{{ $muted }}">Itens abaixo do mínimo e lotes próximos do vencimento.</p>
            </div>
            <a href="{{ $links['inventory'] ?? '#' }}" class="text-sm font-medium text-amber-600 hover:text-amber-500">Abrir estoque</a>
        </div>

        <div class="space-y-3">
            @forelse ($snapshot['alerts']['low_stock_items'] as $item)
                <article class="rounded-xl border border-orange-200/70 bg-orange-50/70 p-3 dark:border-orange-900/60 dark:bg-orange-950/40">
                    <div class="font-medium text-gray-950 dark:text-white">{{ $item->name }}</div>
                    <div class="{{ $muted }}">Atual: {{ number_format((float) $item->current_stock, 3, ',', '.') }} | Mínimo: {{ number_format((float) $item->minimum_stock, 3, ',', '.') }}</div>
                    <div class="{{ $muted }}">{{ $item->unit?->name ?? 'Sem unidade' }}</div>
                </article>
            @empty
                <p class="{{ $muted }}">Sem alertas de estoque mínimo no momento.</p>
            @endforelse

            @if ($snapshot['alerts']['expiring_batches']->isNotEmpty())
                <div class="rounded-xl border border-yellow-200/70 bg-yellow-50/70 p-3 dark:border-yellow-900/60 dark:bg-yellow-950/40">
                    <div class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Lotes vencendo em até 30 dias</div>
                    <div class="space-y-2">
                        @foreach ($snapshot['alerts']['expiring_batches'] as $batch)
                            <div class="{{ $muted }}">
                                {{ $batch->item?->name ?? 'Item sem nome' }} | vence em {{ optional($batch->expires_at)->format('d/m/Y') ?? '-' }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="{{ $cardClasses }}">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="{{ $title }}">Pacientes faltosos</h3>
                <p class="{{ $muted }}">Reincidência de no-show para recuperação dirigida.</p>
            </div>
            <a href="{{ $links['patients'] ?? '#' }}" class="text-sm font-medium text-amber-600 hover:text-amber-500">Abrir pacientes</a>
        </div>

        <div class="space-y-3">
            @forelse ($snapshot['alerts']['repeat_no_show_patients'] as $row)
                <article class="rounded-xl border border-fuchsia-200/70 bg-fuchsia-50/70 p-3 dark:border-fuchsia-900/60 dark:bg-fuchsia-950/40">
                    <div class="font-medium text-gray-950 dark:text-white">{{ $row->patient?->name ?? 'Paciente sem nome' }}</div>
                    <div class="{{ $muted }}">{{ (int) $row->misses }} faltas nos últimos 120 dias</div>
                    <div class="{{ $muted }}">Última falta: {{ optional($row->last_no_show_at)->format('d/m/Y H:i') ?? '-' }}</div>
                </article>
            @empty
                <p class="{{ $muted }}">Nenhum paciente ultrapassou o limite de reincidência definido.</p>
            @endforelse
        </div>
    </section>

    <section class="{{ $cardClasses }}">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="{{ $title }}">Reativação recomendada</h3>
                <p class="{{ $muted }}">Pacientes sem retorno há mais de 90 dias e sem agenda futura.</p>
            </div>
            <a href="{{ $links['operations'] ?? '#' }}" class="text-sm font-medium text-amber-600 hover:text-amber-500">Abrir central</a>
        </div>

        <div class="space-y-3">
            @forelse ($snapshot['alerts']['reactivation_candidates'] as $patient)
                <article class="rounded-xl border border-sky-200/70 bg-sky-50/70 p-3 dark:border-sky-900/60 dark:bg-sky-950/40">
                    <div class="font-medium text-gray-950 dark:text-white">{{ $patient->name }}</div>
                    <div class="{{ $muted }}">Última visita: {{ optional($patient->last_visit_at)->format('d/m/Y H:i') ?? '-' }}</div>
                    <div class="{{ $muted }}">{{ $patient->unit?->name ?? 'Sem unidade' }}</div>
                </article>
            @empty
                <p class="{{ $muted }}">Nenhum paciente em janela crítica de reativação no escopo atual.</p>
            @endforelse
        </div>
    </section>
</div>
