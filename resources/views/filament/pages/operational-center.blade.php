<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        Gestão em tempo real
                    </span>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Central operacional da clínica</h2>
                    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Escopo atual: {{ $snapshot['scope']['label'] }}. Esta visão concentra riscos de agenda, financeiro, estoque e relacionamento para reduzir o tempo entre o alerta e a ação.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="refreshInsights"
                    class="inline-flex items-center justify-center rounded-xl bg-gray-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                >
                    Atualizar leitura
                </button>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Consultas hoje</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['stats']['today_appointments'] }}</div>
                <p class="mt-2 text-sm text-amber-600 dark:text-amber-300">{{ $snapshot['stats']['today_pending_confirmation'] }} aguardando confirmação</p>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Taxa de confirmação</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format((float) $snapshot['stats']['confirmation_rate'], 1, ',', '.') }}%</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Janela móvel dos últimos 7 dias</p>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Inadimplência vencida</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format((float) $snapshot['stats']['overdue_total'], 2, ',', '.') }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Prioridade de cobrança imediata</p>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Itens em estoque crítico</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['stats']['critical_stock_count'] }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Abaixo do mínimo cadastrado</p>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Lotes vencendo</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['stats']['expiring_batch_count'] }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Prazo de até 30 dias</p>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">No-show</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format((float) $snapshot['stats']['no_show_rate'], 1, ',', '.') }}%</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Últimos 30 dias</p>
            </article>
        </section>

        @include('filament.partials.operations-alert-grid', [
            'snapshot' => $snapshot,
            'links' => [
                'appointments' => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('index'),
                'receivables' => \App\Filament\Resources\AccountReceivables\AccountReceivableResource::getUrl('index'),
                'inventory' => \App\Filament\Resources\InventoryItems\InventoryItemResource::getUrl('index'),
                'patients' => \App\Filament\Resources\Patients\PatientResource::getUrl('index'),
                'operations' => \App\Filament\Pages\OperationalCenter::getUrl(),
            ],
            'compact' => false,
        ])
    </div>
</x-filament-panels::page>
