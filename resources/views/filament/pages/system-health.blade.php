@php
    $requirements = $snapshot['environment']['requirements'];
    $statusPill = function (bool $ok): string {
        return $ok
            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
            : 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300';
    };
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-950 dark:text-white">Saúde técnica do sistema</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Ambiente, filas, integrações e últimos sinais de operação do produto em tempo real.
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
                <div class="text-sm text-gray-500 dark:text-gray-400">Instalação</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ $snapshot['environment']['installed'] ? 'Ativa' : 'Pendente' }}
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $snapshot['environment']['app_env'] }} | PHP {{ $snapshot['environment']['php_version'] }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Fila pendente</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['infrastructure']['queue_jobs_pending'] }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Connection: {{ $snapshot['environment']['queue_connection'] }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">Falhas de fila</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['infrastructure']['queue_failed_jobs'] }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Acompanhe para não acumular execução travada</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <div class="text-sm text-gray-500 dark:text-gray-400">WhatsApp 7 dias</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $snapshot['messaging']['sent_last_7d'] }}</div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $snapshot['messaging']['blocked_last_7d'] }} bloqueados | {{ $snapshot['messaging']['failed_last_7d'] }} falhos
                </p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950 xl:col-span-2">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Checklist do ambiente</h3>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Runtime</div>
                        <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                            <div>Aplicação: {{ $snapshot['environment']['app_name'] }}</div>
                            <div>URL: {{ $snapshot['environment']['app_url'] }}</div>
                            <div>Banco: {{ $snapshot['environment']['db_connection'] }}</div>
                            <div>Sessão: {{ $snapshot['environment']['session_driver'] }}</div>
                            <div>Cache: {{ $snapshot['environment']['cache_store'] }}</div>
                            <div>Timezone: {{ $snapshot['environment']['timezone'] }}</div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Infraestrutura</div>
                        <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex items-center justify-between gap-2">
                                <span>Storage gravável</span>
                                <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($snapshot['infrastructure']['storage_writable']) }}">
                                    {{ $snapshot['infrastructure']['storage_writable'] ? 'OK' : 'Falha' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span>Bootstrap/cache gravável</span>
                                <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($snapshot['infrastructure']['cache_writable']) }}">
                                    {{ $snapshot['infrastructure']['cache_writable'] ? 'OK' : 'Falha' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span>Link público do storage</span>
                                <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($snapshot['infrastructure']['public_storage_link']) }}">
                                    {{ $snapshot['infrastructure']['public_storage_link'] ? 'OK' : 'Ausente' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span>PHP mínimo</span>
                                <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($requirements['php']['ok']) }}">
                                    {{ $requirements['php']['current'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Extensões PHP</div>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($requirements['extensions'] as $extension)
                                <div class="flex items-center justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $extension['label'] }}</span>
                                    <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($extension['ok']) }}">
                                        {{ $extension['ok'] ? 'OK' : 'Falta' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Integrações</div>
                        <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                            @foreach ($snapshot['integrations'] as $name => $integration)
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ strtoupper($name) }}</span>
                                    <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($integration['configured']) }}">
                                        {{ $integration['configured'] ? 'Configurado' : 'Pendente' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Últimos webhooks</h3>
                <div class="mt-4 space-y-4">
                    @foreach (['mercadopago' => 'Mercado Pago', 'evolution' => 'Evolution API'] as $key => $label)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-gray-950 dark:text-white">{{ $label }}</div>
                                <span class="rounded-full border px-2 py-1 text-xs {{ $statusPill($snapshot['webhooks'][$key]['status'] === 'processed') }}">
                                    {{ $snapshot['webhooks'][$key]['label'] }}
                                </span>
                            </div>
                            <div class="mt-2 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                                <div>Tentativas: {{ $snapshot['webhooks'][$key]['attempts'] }}</div>
                                <div>Último recebimento: {{ optional($snapshot['webhooks'][$key]['last_received_at'])->format('d/m/Y H:i:s') ?: 'Nunca' }}</div>
                                <div>Processado em: {{ optional($snapshot['webhooks'][$key]['processed_at'])->format('d/m/Y H:i:s') ?: '-' }}</div>
                                @if ($snapshot['webhooks'][$key]['error_message'])
                                    <div class="text-rose-600 dark:text-rose-300">{{ $snapshot['webhooks'][$key]['error_message'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="font-medium text-gray-950 dark:text-white">Falhas recentes</div>
                        <div class="mt-2 space-y-2">
                            @forelse ($snapshot['webhooks']['recent_failures'] as $failure)
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ strtoupper($failure['provider']) }} | {{ $failure['event_name'] ?: 'sem evento' }} | {{ optional($failure['failed_at'])->format('d/m/Y H:i') ?: '-' }}
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">Nenhuma falha recente registrada.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
