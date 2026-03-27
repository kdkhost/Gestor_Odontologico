<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                        Gestão avançada
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Business intelligence e metas</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Escopo atual: {{ $snapshot['scope']['label'] ?? 'Todas as unidades' }}. Use esta visão para acompanhar receita, produtividade, comissão, repasses e progresso das metas.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ $this->exportUrl('summary') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                        Exportar resumo
                    </a>
                    <a href="{{ $this->exportUrl('professionals') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                        Exportar profissionais
                    </a>
                    <a href="{{ $this->exportUrl('targets') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                        Exportar metas
                    </a>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="grid gap-4 xl:grid-cols-4">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Unidade</span>
                    <select wire:model.defer="filters.unit_id" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Todas as unidades</option>
                        @foreach ($unitOptions as $unitId => $unitName)
                            <option value="{{ $unitId }}">{{ $unitName }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">De</span>
                    <input wire:model.defer="filters.from" type="date" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Até</span>
                    <input wire:model.defer="filters.to" type="date" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <div class="flex items-end">
                    <x-filament::button type="button" wire:click="refreshSnapshot" class="w-full">
                        Atualizar leitura
                    </x-filament::button>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Receita recebida</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['revenue_received'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $snapshot['period']['days'] ?? 0 }} dias na janela selecionada</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Atendimentos concluídos</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['summary']['completed_appointments'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Produção efetivamente entregue</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Novos pacientes</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['summary']['new_patients'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Captação no período</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ticket médio</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['average_ticket'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Por conta recebida</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Comissão gerada</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['commission_generated'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Valor calculado para o período</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Comissão pendente</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['commission_pending'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Saldo aguardando repasse</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Comissão paga</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['commission_paid'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Valores já baixados no repasse</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Repasses pagos</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['settlements_paid'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Valor bruto já transferido aos profissionais</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Repasses conciliados</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['settlements_reconciled'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Valor já conferido com o extrato bancário</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Aguardando conciliação</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($snapshot['summary']['settlements_pending_reconciliation'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Repasses pagos sem conferência final</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Metas gerais e por unidade</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Progresso contra metas financeiras e operacionais no escopo administrativo.</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\PerformanceTargets\PerformanceTargetResource::getUrl('index') }}" class="text-sm font-medium text-sky-700 dark:text-sky-300">Gerenciar metas</a>
                </div>

                <div class="space-y-4">
                    @forelse (($snapshot['targets'] ?? []) as $target)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $target['label'] }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $target['scope_label'] }} | {{ $target['period_label'] }}</div>
                                </div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ number_format((float) $target['progress'], 1, ',', '.') }}%</div>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-sky-500" style="width: {{ min(100, max(0, (float) $target['progress'])) }}%"></div>
                            </div>
                            <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                Meta: {{ number_format((float) $target['target_value'], 2, ',', '.') }} | Realizado: {{ number_format((float) $target['current_value'], 2, ',', '.') }}
                            </div>
                            @if ($target['notes'])
                                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $target['notes'] }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Nenhuma meta ativa encontrada para o período atual.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Profissionais em destaque</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Receita, produção e comissão agregadas por profissional.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="py-3 pr-4">Profissional</th>
                                <th class="py-3 pr-4">Atend.</th>
                                <th class="py-3 pr-4">Receita</th>
                                <th class="py-3">Comissão</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                            @forelse (($snapshot['professionals'] ?? []) as $row)
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="py-3 pr-4">
                                        <div class="font-medium">{{ $row['professional_name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['unit_name'] }}</div>
                                    </td>
                                    <td class="py-3 pr-4">{{ $row['completed_appointments'] }}</td>
                                    <td class="py-3 pr-4">R$ {{ number_format((float) $row['revenue_received'], 2, ',', '.') }}</td>
                                    <td class="py-3">
                                        <div>R$ {{ number_format((float) $row['commission_generated'], 2, ',', '.') }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Pendente: R$ {{ number_format((float) $row['commission_pending'], 2, ',', '.') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Nenhuma produção encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Metas individuais por profissional</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Leitura de progresso real por profissional com base em produção, receita, novos pacientes e comissão gerada.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Profissional</th>
                            <th class="py-3 pr-4">Métrica</th>
                            <th class="py-3 pr-4">Meta</th>
                            <th class="py-3 pr-4">Realizado</th>
                            <th class="py-3">Progresso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse (($snapshot['professional_targets'] ?? []) as $target)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $target['scope_label'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $target['scope_secondary_label'] ?: 'Sem unidade' }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ $target['label'] }}</td>
                                <td class="py-3 pr-4">{{ number_format((float) $target['target_value'], 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">{{ number_format((float) $target['current_value'], 2, ',', '.') }}</td>
                                <td class="py-3">
                                    <div class="flex min-w-[180px] items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, max(0, (float) $target['progress'])) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ number_format((float) $target['progress'], 1, ',', '.') }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma meta individual encontrada para o período atual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Desempenho por unidade</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comparativo de produção e receita entre unidades no mesmo recorte.</p>
                </div>
                <div class="space-y-3">
                    @foreach (($snapshot['units'] ?? []) as $row)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-gray-950 dark:text-white">{{ $row['unit_name'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $row['completed_appointments'] }} atendimentos</div>
                            </div>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Receita recebida: R$ {{ number_format((float) $row['revenue_received'], 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Comissões recentes</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Últimos cálculos registrados a partir do financeiro.</p>
                </div>

                <div class="mb-4 flex flex-wrap gap-4">
                    <a href="{{ \App\Filament\Pages\CommissionSettlementCenter::getUrl() }}" class="text-sm font-medium text-sky-700 dark:text-sky-300">
                        Abrir central de repasses
                    </a>
                    <a href="{{ \App\Filament\Resources\CommissionSettlements\CommissionSettlementResource::getUrl('index') }}" class="text-sm font-medium text-sky-700 dark:text-sky-300">
                        Abrir gestão detalhada
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse (($snapshot['recent_commissions'] ?? []) as $entry)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $entry->professional?->user?->name ?? 'Profissional' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $entry->accountReceivable?->patient?->name ?? 'Paciente não identificado' }} | {{ $entry->unit?->name ?? 'Sem unidade' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) $entry->amount, 2, ',', '.') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ strtoupper($entry->status) }}</div>
                                </div>
                            </div>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Base: R$ {{ number_format((float) $entry->base_amount, 2, ',', '.') }} | Percentual: {{ number_format((float) $entry->percentage, 2, ',', '.') }}%
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Nenhuma comissão calculada no período.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
