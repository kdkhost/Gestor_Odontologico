<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Rodapé e identificação do sistema</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Esses dados aparecem no rodapé do painel administrativo.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Versão do sistema</span>
                    <input wire:model.defer="state.system_version" type="text" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nome do desenvolvedor</span>
                    <input wire:model.defer="state.developer_name" type="text" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">E-mail do desenvolvedor</span>
                    <input wire:model.defer="state.developer_email" type="email" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">WhatsApp do desenvolvedor</span>
                    <input wire:model.defer="state.developer_whatsapp" type="text" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Site ou portfólio</span>
                    <input wire:model.defer="state.developer_site" type="url" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Observação do rodapé</span>
                    <input wire:model.defer="state.developer_footer_note" type="text" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Tour inicial</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">O tour explica a navegação, agenda visual, notificações WhatsApp e configurações do sistema.</p>
            </div>

            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
                    <input wire:model.defer="state.tour_auto_start" type="checkbox">
                    <span>Iniciar tour automaticamente no primeiro acesso de cada usuário</span>
                </label>

                <button
                    type="button"
                    x-data
                    x-on:click="window.dispatchEvent(new CustomEvent('odonto-flow-tour-restart'))"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                >
                    Reiniciar tour agora
                </button>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Regras globais do WhatsApp</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Essas travas operacionais reduzem risco de disparo inadequado e apoiam o uso dentro de boas práticas.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200 md:col-span-2">
                    <input wire:model.defer="state.whatsapp_dispatch_enabled" type="checkbox">
                    <span>Permitir envio automatizado de mensagens operacionais</span>
                </label>

                <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200 md:col-span-2">
                    <input wire:model.defer="state.whatsapp_respect_business_hours" type="checkbox">
                    <span>Restringir disparos à janela comercial configurada</span>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Início da janela comercial</span>
                    <input wire:model.defer="state.whatsapp_business_hours_start" type="text" placeholder="08:00" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Fim da janela comercial</span>
                    <input wire:model.defer="state.whatsapp_business_hours_end" type="text" placeholder="18:00" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Intervalo mínimo entre envios para o mesmo destinatário</span>
                    <input wire:model.defer="state.whatsapp_min_interval_seconds" type="number" min="30" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Máximo global por minuto</span>
                    <input wire:model.defer="state.whatsapp_max_per_minute" type="number" min="1" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Máximo por hora para o mesmo destinatário</span>
                    <input wire:model.defer="state.whatsapp_max_per_hour_per_recipient" type="number" min="1" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Delay padrão antes do envio</span>
                    <input wire:model.defer="state.whatsapp_default_delay_ms" type="number" min="0" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </label>

                <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200 md:col-span-2">
                    <input wire:model.defer="state.whatsapp_require_opt_in" type="checkbox">
                    <span>Exigir sinalização de opt-in no contexto do disparo</span>
                </label>

                <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200 md:col-span-2">
                    <input wire:model.defer="state.whatsapp_link_preview" type="checkbox">
                    <span>Permitir prévia automática de links</span>
                </label>

                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Assinatura padrão</span>
                    <textarea wire:model.defer="state.whatsapp_signature" rows="3" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </label>
            </div>
        </section>

        <div class="flex justify-end">
            <x-filament::button type="submit">
                Salvar configurações
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
