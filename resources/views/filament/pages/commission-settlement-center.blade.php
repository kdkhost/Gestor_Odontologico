<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Fechamento financeiro
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central de repasses</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Feche lotes de comissão por profissional, registre o pagamento, anexe comprovantes e concilie o repasse com rastreabilidade no administrativo.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="refreshData"
                    class="inline-flex items-center justify-center rounded-xl bg-gray-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                >
                    Atualizar leitura
                </button>
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
                    <a href="{{ \App\Filament\Resources\CommissionSettlements\CommissionSettlementResource::getUrl('index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                        Abrir gestão detalhada
                    </a>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Pendências prontas para fechamento</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cada cartão representa um profissional com comissão pendente no período filtrado.</p>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                @forelse ($pendingCandidates as $candidate)
                    <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-base font-semibold text-gray-950 dark:text-white">{{ $candidate['professional_name'] }}</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $candidate['unit_name'] }}</p>
                            </div>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                {{ $candidate['commission_count'] }} itens
                            </span>
                        </div>

                        <div class="mt-4 text-3xl font-semibold text-gray-950 dark:text-white">
                            R$ {{ number_format((float) $candidate['gross_amount'], 2, ',', '.') }}
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Cálculos entre
                            {{ optional($candidate['first_calculated_at'])->format('d/m/Y H:i') ?: '-' }}
                            e
                            {{ optional($candidate['last_calculated_at'])->format('d/m/Y H:i') ?: '-' }}
                        </p>

                        <button
                            type="button"
                            wire:click="createSettlement({{ $candidate['professional_id'] }}, {{ $candidate['unit_id'] ?: 0 }})"
                            class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500"
                        >
                            Fechar repasse
                        </button>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-300 p-8 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400 xl:col-span-3">
                        Nenhuma comissão pendente encontrada para o recorte atual.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Últimos repasses</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Histórico recente de lotes fechados, pagos, conciliados ou cancelados.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Referência</th>
                            <th class="py-3 pr-4">Profissional</th>
                            <th class="py-3 pr-4">Período</th>
                            <th class="py-3 pr-4">Itens</th>
                            <th class="py-3 pr-4">Valor</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($recentSettlements as $settlement)
                            @php
                                $status = $settlement['status'] ?? null;
                                $isReconciled = !empty($settlement['reconciled_at']);
                                $statusDetail = match (true) {
                                    $status === 'closed' => 'Aguardando pagamento',
                                    $status === 'paid' && $isReconciled => 'Conciliado',
                                    $status === 'paid' => 'Aguardando conciliação',
                                    $status === 'cancelled' => 'Cancelado',
                                    default => 'Sem leitura',
                                };
                            @endphp
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $settlement['reference'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $settlement['unit']['name'] ?? 'Sem unidade' }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ $settlement['professional']['user']['name'] ?? 'Profissional' }}</td>
                                <td class="py-3 pr-4">
                                    {{ optional($settlement['period_start'] ? \Illuminate\Support\Carbon::parse($settlement['period_start']) : null)->format('d/m/Y') ?: '-' }}
                                    a
                                    {{ optional($settlement['period_end'] ? \Illuminate\Support\Carbon::parse($settlement['period_end']) : null)->format('d/m/Y') ?: '-' }}
                                </td>
                                <td class="py-3 pr-4">{{ $settlement['commission_count'] }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) $settlement['gross_amount'], 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    <div>{{ strtoupper((string) $status) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $statusDetail }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($status === 'closed')
                                            <button
                                                type="button"
                                                wire:click="markAsPaid({{ $settlement['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-500"
                                            >
                                                Marcar pago
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="cancelSettlement({{ $settlement['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/20"
                                            >
                                                Cancelar
                                            </button>
                                        @elseif ($status === 'paid' && ! $isReconciled)
                                            <a
                                                href="{{ \App\Filament\Resources\CommissionSettlements\CommissionSettlementResource::getUrl('index') }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-sky-300 px-3 py-1.5 text-xs font-medium text-sky-700 transition hover:bg-sky-50 dark:border-sky-800 dark:text-sky-300 dark:hover:bg-sky-950/20"
                                            >
                                                Conciliar no detalhe
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $status === 'paid' ? 'Repasse concluído' : 'Sem ações' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum repasse registrado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
