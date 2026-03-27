<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Régua operacional automática</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Configure lembretes, cobrança preventiva e reativação com envio seguro e rastreável pelo WhatsApp.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-filament::button color="gray" wire:click="runPreview">
                        Executar prévia
                    </x-filament::button>
                    <x-filament::button wire:click="runNow">
                        Executar agora
                    </x-filament::button>
                </div>
            </div>
        </section>

        <form wire:submit="save" class="space-y-6">
            <section class="grid gap-4 xl:grid-cols-3">
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Lembrete de consulta</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Dispara somente para consultas confirmadas dentro da janela planejada.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
                            <input wire:model.defer="state.appointment_reminder_enabled" type="checkbox">
                            <span>Ativar automação</span>
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Horas antes da consulta</span>
                            <input wire:model.defer="state.appointment_reminder_hours_before" type="number" min="1" max="168" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </label>
                    </div>
                </article>

                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Cobrança preventiva</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Envia aviso operacional para parcelas abertas ou vencidas dentro do horizonte configurado.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
                            <input wire:model.defer="state.financial_due_enabled" type="checkbox">
                            <span>Ativar automação</span>
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Dias antes do vencimento</span>
                            <input wire:model.defer="state.financial_due_days_before" type="number" min="0" max="30" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </label>
                    </div>
                </article>

                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Reativação de pacientes</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Busca pacientes ativos sem nova agenda e respeita cooldown para evitar insistência indevida.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
                            <input wire:model.defer="state.patient_reactivation_enabled" type="checkbox">
                            <span>Ativar automação</span>
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Reativar após quantos dias sem visita</span>
                            <input wire:model.defer="state.patient_reactivation_after_days" type="number" min="30" max="365" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </label>

                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Cooldown entre tentativas</span>
                            <input wire:model.defer="state.reactivation_cooldown_days" type="number" min="1" max="180" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </label>
                    </div>
                </article>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-amber-950 dark:text-amber-200">Execução automática no cPanel</h3>
                        <p class="mt-2 text-sm text-amber-900/80 dark:text-amber-100/80">
                            Configure um cron a cada 15 minutos para manter a régua ativa sem disparos contínuos fora da governança definida.
                        </p>
                    </div>

                    <code class="rounded-xl border border-amber-300 bg-white px-4 py-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-gray-950 dark:text-amber-100">
                        php {{ base_path('artisan') }} schedule:run >> /dev/null 2>&1
                    </code>
                </div>
            </section>

            <div class="flex justify-end">
                <x-filament::button type="submit">
                    Salvar regras
                </x-filament::button>
            </div>
        </form>

        @if ($lastResults !== [])
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Última execução manual</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($lastResults as $type => $result)
                        <article class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ str($type)->replace('_', ' ')->title() }}</div>
                            <div class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                                <div>Status: {{ $result['status'] ?? '-' }}</div>
                                <div>Encontrados: {{ $result['matched_count'] ?? 0 }}</div>
                                <div>Enviados: {{ $result['sent_count'] ?? 0 }}</div>
                                <div>Ignorados: {{ $result['skipped_count'] ?? 0 }}</div>
                                <div>Falhos: {{ $result['failed_count'] ?? 0 }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Últimos logs da automação</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Tipo</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Encontrados</th>
                            <th class="py-3 pr-4">Enviados</th>
                            <th class="py-3 pr-4">Ignorados</th>
                            <th class="py-3 pr-4">Falhos</th>
                            <th class="py-3 pr-4">Início</th>
                            <th class="py-3">Fim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-900">
                        @forelse ($logs as $log)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="py-3 pr-4">{{ str($log['automation_type'])->replace('_', ' ')->title() }}</td>
                                <td class="py-3 pr-4">{{ $log['status'] }}</td>
                                <td class="py-3 pr-4">{{ $log['matched_count'] }}</td>
                                <td class="py-3 pr-4">{{ $log['sent_count'] }}</td>
                                <td class="py-3 pr-4">{{ $log['skipped_count'] }}</td>
                                <td class="py-3 pr-4">{{ $log['failed_count'] }}</td>
                                <td class="py-3 pr-4">{{ isset($log['started_at']) ? \Illuminate\Support\Carbon::parse($log['started_at'])->format('d/m/Y H:i:s') : '-' }}</td>
                                <td class="py-3">{{ isset($log['finished_at']) && $log['finished_at'] ? \Illuminate\Support\Carbon::parse($log['finished_at'])->format('d/m/Y H:i:s') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma execução registrada ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
