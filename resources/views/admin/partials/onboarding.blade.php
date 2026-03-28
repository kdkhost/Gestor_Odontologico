@if (auth()->check())
    @php
        $settings = app(\App\Services\SettingService::class);
        $autoStart = $settings->get('onboarding', 'auto_start', config('clinic.onboarding.auto_start'));
    @endphp

    <script>
        (() => {
            const userId = @json(auth()->id());
            const autoStart = @json((bool) $autoStart);
            const storageKey = `odonto-flow-adminlte-tour:${userId}`;

            const getSteps = () => ([
                {
                    element: document.querySelector('.main-header'),
                    intro: 'Aqui ficam os atalhos de sessao, fullscreen e troca rapida entre telas administrativas.',
                },
                {
                    element: document.querySelector('.main-sidebar'),
                    intro: 'A barra lateral organiza a clinica por operacao, financeiro, apoio, convenios e administracao.',
                },
                {
                    element: document.querySelector('[data-admin-tour="dashboard-hero"]'),
                    intro: 'Este bloco resume o turno atual e concentra o acesso rapido para reiniciar o tour ou abrir a agenda.',
                },
                {
                    element: document.querySelector('[data-admin-tour="system-health"]'),
                    intro: 'Aqui voce monitora requisitos do ambiente, filas, storage e webhooks criticos do sistema.',
                },
                {
                    element: document.querySelector('[data-admin-tour="module-grid"]'),
                    intro: 'Os modulos foram reorganizados em cards para acelerar a navegacao operacional.',
                },
                {
                    element: document.querySelector('[data-admin-tour="workspace-frame"]'),
                    intro: 'Cada workspace incorpora a tela operacional dentro do shell AdminLTE, reduzindo a sensacao de painel fragmentado.',
                },
            ]).filter((step) => step.element);

            const runTour = (force = false) => {
                if (! window.introJs) {
                    return;
                }

                if (! force && (! autoStart || localStorage.getItem(storageKey))) {
                    return;
                }

                const steps = getSteps();

                if (! steps.length) {
                    return;
                }

                const intro = window.introJs();

                intro.setOptions({
                    steps,
                    nextLabel: 'Proximo',
                    prevLabel: 'Voltar',
                    doneLabel: 'Concluir',
                    skipLabel: 'Fechar',
                    showProgress: true,
                });

                intro.oncomplete(() => localStorage.setItem(storageKey, '1'));
                intro.onexit(() => localStorage.setItem(storageKey, '1'));
                intro.start();
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action="restart-tour"]');

                if (! button) {
                    return;
                }

                localStorage.removeItem(storageKey);
                runTour(true);
            });

            document.addEventListener('DOMContentLoaded', () => runTour(false));
        })();
    </script>
@endif
