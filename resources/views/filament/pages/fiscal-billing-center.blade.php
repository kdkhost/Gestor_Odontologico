<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        NFSe e faturamento
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central de faturamento fiscal</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Gere rascunhos fiscais a partir das contas pagas, coloque notas na fila, registre protocolo e acompanhe a emissao da NFSe com rastreabilidade.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="createAllDrafts"
                        class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500"
                    >
                        Gerar rascunhos
                    </button>
                    <button
                        type="button"
                        wire:click="submitPending"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                    >
                        Enviar fila fiscal
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
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ate</span>
                    <input wire:model.defer="filters.to" type="date" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <div class="flex items-end">
                    <a href="{{ \App\Filament\Resources\FiscalInvoices\FiscalInvoiceResource::getUrl('index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                        Abrir detalhe NFSe
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Contas elegiveis</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $summary['eligible_count'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pagas e prontas para NFSe</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Valor elegivel</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($summary['eligible_amount'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Base pronta para faturamento</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Rascunhos + fila</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ ($summary['draft_count'] ?? 0) + ($summary['pending_count'] ?? 0) }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Notas aguardando protocolo ou emissao</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Emitidas no periodo</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) ($summary['issued_amount'] ?? 0), 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Volume fiscal efetivamente emitido</p>
            </article>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Contas pagas prontas para NFSe</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">O sistema bloqueia a geracao se a unidade ainda nao tiver dados fiscais minimos configurados.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Conta</th>
                            <th class="py-3 pr-4">Paciente</th>
                            <th class="py-3 pr-4">Unidade</th>
                            <th class="py-3 pr-4">Valor</th>
                            <th class="py-3 pr-4">Pronto</th>
                            <th class="py-3">Acao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($eligibleReceivables as $candidate)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $candidate['reference'] ?: 'Sem referencia' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $candidate['description'] }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ $candidate['patient_name'] }}</td>
                                <td class="py-3 pr-4">{{ $candidate['unit_name'] }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) $candidate['amount'], 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    @if ($candidate['is_ready'])
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            OK
                                        </span>
                                    @else
                                        <div class="text-xs text-rose-600 dark:text-rose-300">
                                            {{ implode(', ', $candidate['missing_fields']) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if ($candidate['is_ready'])
                                        <button
                                            type="button"
                                            wire:click="createDraft({{ $candidate['account_receivable_id'] }})"
                                            class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-500"
                                        >
                                            Gerar NFSe
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Ajustar unidade</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma conta elegivel encontrada no recorte atual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Ultimas notas fiscais</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rascunhos, fila, protocolo, emissao e cancelamento com trilha operacional.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Referencia</th>
                            <th class="py-3 pr-4">Paciente</th>
                            <th class="py-3 pr-4">Valor</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Provedor</th>
                            <th class="py-3 pr-4">Municipal</th>
                            <th class="py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($recentInvoices as $invoice)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $invoice['reference'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice['unit']['name'] ?? 'Sem unidade' }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ $invoice['patient']['name'] ?? 'Paciente' }}</td>
                                <td class="py-3 pr-4">R$ {{ number_format((float) $invoice['amount'], 2, ',', '.') }}</td>
                                <td class="py-3 pr-4">{{ strtoupper($invoice['status']) }}</td>
                                <td class="py-3 pr-4">{{ strtoupper($invoice['provider_profile']) }}</td>
                                <td class="py-3 pr-4">{{ $invoice['municipal_invoice_number'] ?: '-' }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if (($invoice['status'] ?? null) === 'draft')
                                            <button
                                                type="button"
                                                wire:click="queueInvoice({{ $invoice['id'] }})"
                                                class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-sky-500"
                                            >
                                                Enviar fila
                                            </button>
                                        @endif

                                        <a
                                            href="{{ \App\Filament\Resources\FiscalInvoices\FiscalInvoiceResource::getUrl('index') }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                                        >
                                            Abrir detalhe
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma NFSe registrada ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
