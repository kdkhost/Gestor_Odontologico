<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        TISS ready
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central de faturamento de convenio</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Controle o ciclo entre item executado, lote faturado, retorno da operadora, glosa e reapresentacao com exportacao JSON estruturada.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="refreshData"
                        class="inline-flex items-center justify-center rounded-xl bg-gray-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                    >
                        Atualizar leitura
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="grid gap-4 xl:grid-cols-2">
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
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status do lote</span>
                    <select wire:model.defer="filters.status" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Todos</option>
                        @foreach ($statusOptions as $status => $label)
                            <option value="{{ $status }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Prontos para faturar</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $summary['pending_billing_count'] ?? 0 }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Itens executados e autorizados</div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Lotes em trabalho</div>
                <div class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-300">{{ ($summary['draft_batches_count'] ?? 0) + ($summary['submitted_batches_count'] ?? 0) }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Rascunho + enviados</div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Recebido de convenio</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-300">R$ {{ number_format((float) ($summary['received_total'] ?? 0), 2, ',', '.') }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Base de retorno da operadora</div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Glosa e reapresentacao</div>
                <div class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-300">{{ ($summary['glossed_items_count'] ?? 0) + ($summary['representation_candidates_count'] ?? 0) }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Glosados + prontos para reapresentar</div>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Agrupamentos prontos para criar lote</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cada linha agrupa itens autorizados e ja executados, separados por convenio e competencia de faturamento.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Convenio</th>
                            <th class="py-3 pr-4">Competencia</th>
                            <th class="py-3 pr-4">Itens</th>
                            <th class="py-3 pr-4">Pacientes</th>
                            <th class="py-3 pr-4">Valor</th>
                            <th class="py-3 pr-4">Janela executada</th>
                            <th class="py-3">Acao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($pendingGroups as $group)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $group['insurance_plan_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $group['unit_name'] }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $group['competence_month'])->format('m/Y') }}</td>
                                <td class="py-3 pr-4">{{ $group['eligible_items_count'] }}</td>
                                <td class="py-3 pr-4">{{ $group['patient_count'] }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) $group['claimed_total'], 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    {{ $group['first_execution_at'] ? \Illuminate\Support\Carbon::parse($group['first_execution_at'])->format('d/m/Y') : '-' }}
                                    ate
                                    {{ $group['last_execution_at'] ? \Illuminate\Support\Carbon::parse($group['last_execution_at'])->format('d/m/Y') : '-' }}
                                </td>
                                <td class="py-3">
                                    <button
                                        type="button"
                                        wire:click="createBatch({{ $group['insurance_plan_id'] }}, '{{ $group['competence_month'] }}')"
                                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-500"
                                    >
                                        Gerar lote
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum agrupamento pronto para faturamento de convenio no recorte atual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Lotes recentes</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fluxo recomendado: gerar lote, enviar, registrar retorno integral ou parcial e criar reapresentacao quando houver glosa.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Lote</th>
                            <th class="py-3 pr-4">Competencia</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Guias</th>
                            <th class="py-3 pr-4">Faturado</th>
                            <th class="py-3 pr-4">Recebido</th>
                            <th class="py-3 pr-4">Glosa</th>
                            <th class="py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($recentBatches as $batch)
                            @php
                                $badge = match ($batch['status']) {
                                    'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'partial_gloss' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'glossed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    'submitted' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
                                };
                            @endphp
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $batch['reference'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $batch['insurance_plan']['name'] ?? 'Convenio' }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $batch['competence_month'])->format('m/Y') }}</td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">
                                        {{ strtoupper($statusOptions[$batch['status']] ?? $batch['status']) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">{{ $batch['guide_count'] }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) ($batch['claimed_total'] ?? 0), 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) ($batch['received_total'] ?? 0), 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) ($batch['gloss_total'] ?? 0), 2, ',', '.') }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if (($batch['status'] ?? null) === 'draft')
                                            <button
                                                type="button"
                                                wire:click="submitBatch({{ $batch['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-gray-950 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                                            >
                                                Enviar
                                            </button>
                                        @endif

                                        @if (in_array($batch['status'] ?? null, ['submitted', 'partial_gloss'], true))
                                            <button
                                                type="button"
                                                wire:click="registerPaidReturn({{ $batch['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-500"
                                            >
                                                Retorno pago
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="registerPartialGlossReturn({{ $batch['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-950/30"
                                            >
                                                Retorno parcial
                                            </button>
                                        @endif

                                        @if (in_array($batch['status'] ?? null, ['partial_gloss', 'glossed'], true))
                                            <button
                                                type="button"
                                                wire:click="createRepresentationBatch({{ $batch['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-950/30"
                                            >
                                                Reapresentar
                                            </button>
                                        @endif

                                        <a
                                            href="{{ route('admin.insurance-claims.export', $batch['id']) }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                                        >
                                            Exportar JSON
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum lote de faturamento de convenio registrado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
