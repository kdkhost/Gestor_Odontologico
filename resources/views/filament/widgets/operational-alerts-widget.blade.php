<x-filament-widgets::widget>
    <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Radar de prioridades</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Escopo atual: {{ $snapshot['scope']['label'] }}. Use esta leitura para agir antes que o gargalo vire problema.
                </p>
            </div>

            <a
                href="{{ $links['operations'] }}"
                class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-400"
            >
                Abrir central operacional
            </a>
        </div>

        @include('filament.partials.operations-alert-grid', [
            'snapshot' => $snapshot,
            'links' => $links,
            'compact' => true,
        ])
    </div>
</x-filament-widgets::widget>
