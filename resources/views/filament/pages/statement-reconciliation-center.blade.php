<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                        Conciliacao assistida
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Importacao de extrato</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Importe arquivos CSV, TXT ou OFX do banco para sugerir conciliacao dos repasses com base em valor, referencia, data e descricao.
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

        <form wire:submit="importStatement" class="space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Novo extrato</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Use preferencialmente um arquivo com colunas de data, descricao, valor e referencia. O sistema tenta identificar esses campos automaticamente.
                    </p>
                </div>

                <div class="grid gap-4 xl:grid-cols-5">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Unidade</span>
                        <select wire:model.defer="importState.unit_id" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Todas as unidades</option>
                            @foreach ($unitOptions as $unitId => $unitName)
                                <option value="{{ $unitId }}">{{ $unitName }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Formato</span>
                        <select wire:model.defer="importState.file_type" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach ($fileTypeOptions as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Delimitador</span>
                        <select wire:model.defer="importState.delimiter" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="auto">Detectar automaticamente</option>
                            <option value=";">Ponto e virgula (;)</option>
                            <option value=",">Virgula (,)</option>
                            <option value="tab">Tabulacao</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Perfil do banco</span>
                        <select wire:model.defer="importState.bank_profile" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach ($bankProfileOptions as $profile => $label)
                                <option value="{{ $profile }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Arquivo</span>
                        <input wire:model="statementFile" type="file" accept=".csv,.txt,.ofx" class="fi-input block w-full rounded-xl border-gray-300 bg-white text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </label>
                    <label class="flex items-end gap-3 text-sm text-gray-700 dark:text-gray-200">
                        <input wire:model.defer="importState.has_header" type="checkbox">
                        <span>Primeira linha contem cabecalho</span>
                    </label>
                </div>

                <div class="mt-5 flex justify-end">
                    <x-filament::button type="submit">
                        Importar extrato
                    </x-filament::button>
                </div>
            </section>
        </form>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Sugestoes abertas</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Linhas importadas com sugestao confiavel de repasse ainda nao conciliado.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Importacao</th>
                            <th class="py-3 pr-4">Data</th>
                            <th class="py-3 pr-4">Descricao</th>
                            <th class="py-3 pr-4">Valor</th>
                            <th class="py-3 pr-4">Sugestao</th>
                            <th class="py-3 pr-4">Confianca</th>
                            <th class="py-3">Acao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($suggestions as $line)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $line['statement_import']['original_name'] ?? 'Importacao' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $line['statement_import']['unit']['name'] ?? 'Todas as unidades' }}
                                    </div>
                                </td>
                                <td class="py-3 pr-4">
                                    {{ !empty($line['transaction_date']) ? \Illuminate\Support\Carbon::parse($line['transaction_date'])->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="max-w-xs truncate">{{ $line['description'] ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $line['transaction_reference'] ?? 'Sem referencia' }}</div>
                                </td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) ($line['amount_absolute'] ?? 0), 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $line['suggested_settlement']['professional']['user']['name'] ?? 'Repasse sugerido' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $line['suggested_settlement']['reference'] ?? '-' }} | {{ $line['suggested_settlement']['unit']['name'] ?? 'Sem unidade' }}
                                    </div>
                                </td>
                                <td class="py-3 pr-4">
                                    <div>{{ $line['match_score'] ?? 0 }} pts</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $line['match_reason'] ?? '-' }}</div>
                                </td>
                                <td class="py-3">
                                    <button
                                        type="button"
                                        wire:click="applySuggestion({{ $line['id'] }})"
                                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-500"
                                    >
                                        Conciliar linha
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma sugestao aberta no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Ultimas importacoes</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Historico recente dos arquivos processados e da quantidade de sugestoes realmente conciliadas.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Arquivo</th>
                            <th class="py-3 pr-4">Perfil</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Linhas</th>
                            <th class="py-3 pr-4">Sugestoes</th>
                            <th class="py-3 pr-4">Conciliadas</th>
                            <th class="py-3 pr-4">Importado em</th>
                            <th class="py-3">Acao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($recentImports as $import)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $import['original_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($import['file_type'] ?? 'CSV') }} | {{ $import['unit']['name'] ?? 'Todas as unidades' }}
                                    </div>
                                </td>
                                <td class="py-3 pr-4">{{ strtoupper($import['bank_profile'] ?? 'generic') }}</td>
                                <td class="py-3 pr-4">{{ strtoupper($import['status'] ?? '-') }}</td>
                                <td class="py-3 pr-4">{{ $import['total_lines'] ?? 0 }}</td>
                                <td class="py-3 pr-4">{{ $import['matched_suggestions_count'] ?? 0 }}</td>
                                <td class="py-3 pr-4">{{ $import['reconciled_lines_count'] ?? 0 }}</td>
                                <td class="py-3 pr-4">
                                    {{ !empty($import['imported_at']) ? \Illuminate\Support\Carbon::parse($import['imported_at'])->format('d/m/Y H:i:s') : '-' }}
                                </td>
                                <td class="py-3">
                                    @if (($import['matched_suggestions_count'] ?? 0) > ($import['reconciled_lines_count'] ?? 0))
                                        <button
                                            type="button"
                                            wire:click="applyImportSuggestions({{ $import['id'] }})"
                                            class="inline-flex items-center justify-center rounded-lg border border-sky-300 px-3 py-1.5 text-xs font-medium text-sky-700 transition hover:bg-sky-50 dark:border-sky-800 dark:text-sky-300 dark:hover:bg-sky-950/20"
                                        >
                                            Aplicar sugestoes
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Sem acoes</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma importacao de extrato registrada ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
