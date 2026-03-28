<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Qualidade assistencial
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central de governanca clinica</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Monitore prontuarios faltantes, documentos obrigatorios, itens vencidos do plano e pacientes com tratamento aprovado sem retorno futuro.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="refreshSnapshot"
                    class="inline-flex items-center justify-center rounded-xl bg-gray-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                >
                    Atualizar leitura
                </button>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Prontuarios faltantes</div>
                <div class="mt-2 text-3xl font-semibold text-rose-600 dark:text-rose-300">{{ $snapshot['stats']['missing_clinical_records_count'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Consultas concluidas sem evolucao clinica registrada</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Planos sem retorno</div>
                <div class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-300">{{ $snapshot['stats']['plans_without_followup_count'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tratamentos aprovados ainda ativos e sem nova consulta futura</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Documentos pendentes</div>
                <div class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-300">{{ $snapshot['stats']['pending_documents_count'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pacientes em tratamento com aceite documental incompleto</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Itens vencidos</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['stats']['overdue_plan_items_count'] ?? 0 }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Itens planejados com data passada e sem conclusao</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Consultas concluidas sem prontuario</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use este bloco para cobrar evolucao clinica e fechamento correto do atendimento.</p>
                </div>
                <div class="space-y-3">
                    @forelse ($snapshot['alerts']['completed_without_record'] ?? [] as $appointment)
                        <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $appointment->patient?->name ?? 'Paciente' }}</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $appointment->unit?->name ?? 'Sem unidade' }} · {{ optional($appointment->scheduled_start)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma consulta concluida sem prontuario no recorte atual.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Planos aprovados sem retorno futuro</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sinaliza pacientes com tratamento em aberto e sem agenda futura associada.</p>
                </div>
                <div class="space-y-3">
                    @forelse ($snapshot['alerts']['plans_without_followup'] ?? [] as $plan)
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $plan->patient?->name ?? 'Paciente' }}</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $plan->name }} · {{ $plan->unit?->name ?? 'Sem unidade' }} · {{ $plan->items->count() }} item(ns)
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum plano aprovado sem retorno futuro encontrado.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Documentos obrigatorios pendentes</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Importante para recepcao, clinico e conformidade documental do atendimento.</p>
                </div>
                <div class="space-y-3">
                    @forelse ($snapshot['alerts']['pending_required_documents'] ?? [] as $patient)
                        <div class="rounded-2xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $patient->name }}</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $patient->unit?->name ?? 'Sem unidade' }} · {{ $patient->pending_documents_count }} pendencia(s) de {{ $patient->required_documents_count }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum paciente com pendencia documental no recorte atual.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Itens do plano vencidos</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajuda a coordenar execucao clinica e recuperar etapas planejadas que ficaram para tras.</p>
                </div>
                <div class="space-y-3">
                    @forelse ($snapshot['alerts']['overdue_treatment_items'] ?? [] as $item)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $item->treatmentPlan?->patient?->name ?? 'Paciente' }}</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->description }} · {{ optional($item->scheduled_for)->format('d/m/Y H:i') }} · {{ $item->treatmentPlan?->unit?->name ?? 'Sem unidade' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum item vencido de tratamento encontrado.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
