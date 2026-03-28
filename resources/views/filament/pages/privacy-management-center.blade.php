<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                        Privacidade e conformidade
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central LGPD</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Controle solicitacoes de exportacao e anonimizacao, com prazo, trilha operacional e download protegido do pacote de dados do paciente.
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
            <div class="grid gap-4 xl:grid-cols-3">
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
                        <option value="pending">Pendente</option>
                        <option value="processing">Em processamento</option>
                        <option value="completed">Concluida</option>
                        <option value="failed">Falhou</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tipo</span>
                    <select wire:model.defer="filters.type" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Todos</option>
                        @foreach ($requestTypeOptions as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Solicitacoes pendentes</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $summary['pending_count'] ?? 0 }}</div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Fora do prazo</div>
                <div class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-300">{{ $summary['overdue_count'] ?? 0 }}</div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Concluidas</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-300">{{ $summary['completed_count'] ?? 0 }}</div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Cadastros anonimizados</div>
                <div class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-300">{{ $summary['anonymized_patients_count'] ?? 0 }}</div>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Pacientes candidatos a acao LGPD</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crie rapidamente uma solicitacao de exportacao ou anonimizacao do cadastro com base no recorte atual.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Paciente</th>
                            <th class="py-3 pr-4">Unidade</th>
                            <th class="py-3 pr-4">Ultima visita</th>
                            <th class="py-3 pr-4">Dados sensiveis</th>
                            <th class="py-3 pr-4">Portal</th>
                            <th class="py-3 pr-4">Opt-in WhatsApp</th>
                            <th class="py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($candidatePatients as $patient)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4 font-medium">{{ $patient['name'] }}</td>
                                <td class="py-3 pr-4">{{ $patient['unit_name'] }}</td>
                                <td class="py-3 pr-4">{{ $patient['last_visit_at'] ? \Illuminate\Support\Carbon::parse($patient['last_visit_at'])->format('d/m/Y H:i') : 'Sem registro' }}</td>
                                <td class="py-3 pr-4">{{ $patient['data_points_count'] }}</td>
                                <td class="py-3 pr-4">{{ $patient['has_portal_account'] ? 'Sim' : 'Nao' }}</td>
                                <td class="py-3 pr-4">{{ $patient['has_whatsapp_opt_in'] ? 'Sim' : 'Nao' }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="createRequest({{ $patient['patient_id'] }}, 'export')"
                                            class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-sky-500"
                                        >
                                            Exportar dados
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="createRequest({{ $patient['patient_id'] }}, 'anonymize')"
                                            class="inline-flex items-center justify-center rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-950/30"
                                        >
                                            Anonimizar cadastro
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum paciente elegivel encontrado no recorte atual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Solicitacoes recentes</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fluxo recomendado: registrar a solicitacao, processar e, quando for exportacao, baixar o JSON protegido do titular.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Paciente</th>
                            <th class="py-3 pr-4">Tipo</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Prazo</th>
                            <th class="py-3 pr-4">Solicitado por</th>
                            <th class="py-3 pr-4">Resultado</th>
                            <th class="py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($recentRequests as $request)
                            @php
                                $statusColor = match ($request['status']) {
                                    'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    'processing' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                    default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                };
                            @endphp
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $request['patient']['name'] ?? 'Paciente' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request['unit']['name'] ?? 'Sem unidade' }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ $requestTypeOptions[$request['request_type']] ?? strtoupper($request['request_type']) }}</td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                                        {{ strtoupper($request['status']) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">{{ $request['due_at'] ? \Illuminate\Support\Carbon::parse($request['due_at'])->format('d/m/Y H:i') : '-' }}</td>
                                <td class="py-3 pr-4">{{ $request['requested_by']['name'] ?? ($request['requester_name'] ?? '-') }}</td>
                                <td class="py-3 pr-4">
                                    @if (($request['request_type'] ?? null) === 'export' && !empty($request['export_path']))
                                        Export pronto
                                    @elseif (($request['request_type'] ?? null) === 'anonymize' && ($request['status'] ?? null) === 'completed')
                                        Cadastro anonimizado
                                    @else
                                        {{ $request['last_error_message'] ?? '-' }}
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if (in_array($request['status'], ['pending', 'processing', 'failed'], true))
                                            <button
                                                type="button"
                                                wire:click="processRequest({{ $request['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-gray-950 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                                            >
                                                Processar
                                            </button>
                                        @endif

                                        @if (($request['request_type'] ?? null) === 'export' && !empty($request['export_path']) && ($request['status'] ?? null) === 'completed')
                                            <a
                                                href="{{ route('admin.privacy-exports.download', $request['id']) }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                                            >
                                                Baixar JSON
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma solicitacao LGPD registrada ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
