<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300">
                        Convenios e guias
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central de autorizacoes de convenio</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Organize a fila de guias, acompanhe retorno da operadora, identifique itens negados e exporte um JSON estruturado pronto para futura integracao TISS.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="expireAuthorizations"
                        class="inline-flex items-center justify-center rounded-xl border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-950/30"
                    >
                        Expirar vencidas
                    </button>
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
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Status</span>
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
                <div class="text-sm text-gray-500 dark:text-gray-400">Rascunhos</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $summary['draft_count'] ?? 0 }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Valor solicitado: R$ {{ number_format((float) ($summary['requested_total'] ?? 0), 2, ',', '.') }}</div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Enviadas</div>
                <div class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-300">{{ $summary['submitted_count'] ?? 0 }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Aguardando retorno operacional</div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Autorizadas para agendar</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-300">{{ $summary['authorized_to_schedule_count'] ?? 0 }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Valor autorizado: R$ {{ number_format((float) ($summary['authorized_total'] ?? 0), 2, ',', '.') }}</div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Criticos</div>
                <div class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-300">{{ ($summary['expiring_count'] ?? 0) + ($summary['denied_items_count'] ?? 0) }}</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ $summary['expiring_count'] ?? 0 }} vencendo + {{ $summary['denied_items_count'] ?? 0 }} itens negados
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Planos candidatos para montar guia</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">A central considera convenios com exigencia de autorizacao e procedimentos que pedem aprovacao antes da execucao.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Plano</th>
                            <th class="py-3 pr-4">Paciente</th>
                            <th class="py-3 pr-4">Convenio</th>
                            <th class="py-3 pr-4">Itens</th>
                            <th class="py-3 pr-4">Valor</th>
                            <th class="py-3 pr-4">Origem</th>
                            <th class="py-3">Acao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($candidatePlans as $plan)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $plan['plan_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plan['plan_code'] ?: 'Sem codigo' }}</div>
                                </td>
                                <td class="py-3 pr-4">
                                    <div>{{ $plan['patient_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plan['unit_name'] }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ $plan['insurance_plan_name'] }}</td>
                                <td class="py-3 pr-4">{{ $plan['eligible_items_count'] }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) $plan['requested_total'], 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">{{ strtoupper($plan['needs_authorization_by']) }}</td>
                                <td class="py-3">
                                    <button
                                        type="button"
                                        wire:click="createAuthorization({{ $plan['treatment_plan_id'] }})"
                                        class="inline-flex items-center justify-center rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-cyan-500"
                                    >
                                        Gerar guia
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum plano elegivel para nova autorizacao no recorte atual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Guias recentes</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fluxo sugerido: gerar rascunho, enviar para a operadora, registrar retorno e exportar o JSON estruturado quando precisar integrar com faturamento ou TISS.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Guia</th>
                            <th class="py-3 pr-4">Paciente</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Itens</th>
                            <th class="py-3 pr-4">Solicitado</th>
                            <th class="py-3 pr-4">Autorizado</th>
                            <th class="py-3 pr-4">Validade</th>
                            <th class="py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($recentAuthorizations as $authorization)
                            @php
                                $badge = match ($authorization['status']) {
                                    'authorized' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'partially_authorized' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                    'submitted' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'denied', 'expired' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
                                };
                            @endphp
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $authorization['reference'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $authorization['insurance_plan']['name'] ?? 'Convenio' }}</div>
                                </td>
                                <td class="py-3 pr-4">
                                    <div>{{ $authorization['patient']['name'] ?? 'Paciente' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $authorization['unit']['name'] ?? 'Sem unidade' }}</div>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">
                                        {{ strtoupper($statusOptions[$authorization['status']] ?? $authorization['status']) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">{{ count($authorization['items'] ?? []) }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) ($authorization['requested_total'] ?? 0), 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) ($authorization['authorized_total'] ?? 0), 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    {{ !empty($authorization['valid_until']) ? \Illuminate\Support\Carbon::parse($authorization['valid_until'])->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if (($authorization['status'] ?? null) === 'draft')
                                            <button
                                                type="button"
                                                wire:click="submitAuthorization({{ $authorization['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-gray-950 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                                            >
                                                Enviar
                                            </button>
                                        @endif

                                        @if (($authorization['status'] ?? null) === 'submitted')
                                            <button
                                                type="button"
                                                wire:click="authorizeAuthorization({{ $authorization['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-500"
                                            >
                                                Autorizar
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="denyAuthorization({{ $authorization['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-950/30"
                                            >
                                                Negar
                                            </button>
                                        @endif

                                        <a
                                            href="{{ route('admin.insurance-authorizations.export', $authorization['id']) }}"
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
                                    Nenhuma guia de convenio cadastrada ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
