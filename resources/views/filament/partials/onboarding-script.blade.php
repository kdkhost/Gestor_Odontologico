@if (auth()->check())
    @php
        $settings = app(\App\Services\SettingService::class);
        $autoStart = $settings->get('onboarding', 'auto_start', config('clinic.onboarding.auto_start'));
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
    <script>
        (() => {
            const userId = @json(auth()->id());
            const autoStart = @json((bool) $autoStart);
            const storageKey = `odonto-flow-tour-completed:${userId}`;

            const getSteps = () => ([
                {
                    element: document.querySelector('.fi-topbar'),
                    intro: 'Aqui fica a navegacao principal do painel e o acesso rapido a conta.',
                },
                {
                    element: document.querySelector('.fi-sidebar'),
                    intro: 'Use a barra lateral para navegar entre agenda, cadastros, notificacoes e configuracoes.',
                },
                {
                    element: document.querySelector('a[href*="/admin/visual-agenda"]'),
                    intro: 'A agenda visual mostra os atendimentos por periodo e aceita filtros por unidade, profissional e cadeira.',
                },
                {
                    element: document.querySelector('a[href*="/admin/notification-templates"]'),
                    intro: 'Aqui voce personaliza templates operacionais de WhatsApp, push e e-mail.',
                },
                {
                    element: document.querySelector('a[href*="/admin/business-intelligence-center"]'),
                    intro: 'Nesta central voce acompanha receita, metas, comissao, repasses e exporta dados gerenciais por periodo.',
                },
                {
                    element: document.querySelector('a[href*="/admin/commission-settlement-center"]'),
                    intro: 'Aqui voce fecha repasses de comissao por profissional e controla o pagamento em lote.',
                },
                {
                    element: document.querySelector('a[href*="/admin/commission-settlements"]'),
                    intro: 'Nesta gestao detalhada voce anexa comprovantes, registra referencia bancaria e concilia o repasse manualmente.',
                },
                {
                    element: document.querySelector('a[href*="/admin/statement-reconciliation-center"]'),
                    intro: 'Aqui voce importa extratos do banco para gerar sugestoes de conciliacao assistida por valor, referencia e data.',
                },
                {
                    element: document.querySelector('a[href*="/admin/fiscal-billing-center"]'),
                    intro: 'Nesta central voce transforma contas pagas em NFSe com rascunho, fila fiscal, protocolo e emissao rastreavel.',
                },
                {
                    element: document.querySelector('a[href*="/admin/fiscal-invoices"]'),
                    intro: 'Aqui voce acompanha cada nota fiscal, registra RPS, numero municipal, codigo de verificacao e cancelamento.',
                },
                {
                    element: document.querySelector('a[href*="/admin/clinical-governance-center"]'),
                    intro: 'Nesta central voce monitora prontuarios faltantes, documentos pendentes, itens vencidos do tratamento e planos sem retorno futuro.',
                },
                {
                    element: document.querySelector('a[href*="/admin/insurance-authorization-center"]'),
                    intro: 'Aqui voce monta guias de convenio, registra retorno da operadora, acompanha negacoes e exporta um JSON estruturado para futuras integracoes TISS.',
                },
                {
                    element: document.querySelector('a[href*="/admin/insurance-claim-billing-center"]'),
                    intro: 'Nesta central voce transforma itens autorizados e executados em lotes de faturamento, registra glosa, retorno da operadora e reapresentacao TISS-ready.',
                },
                {
                    element: document.querySelector('a[href*="/admin/privacy-management-center"]'),
                    intro: 'Nesta central voce controla solicitacoes LGPD, exportacao estruturada dos dados do paciente e anonimizacao segura do cadastro.',
                },
                {
                    element: document.querySelector('a[href*="/admin/automation-center"]'),
                    intro: 'Nesta central voce configura a regua automatica de lembretes, cobranca preventiva e reativacao com execucao segura.',
                },
                {
                    element: document.querySelector('a[href*="/admin/system-settings"]'),
                    intro: 'Nesta area voce ajusta rodape, versao, tour e regras globais de seguranca do WhatsApp.',
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

            window.addEventListener('odonto-flow-tour-restart', () => {
                localStorage.removeItem(storageKey);
                runTour(true);
            });

            document.addEventListener('DOMContentLoaded', () => runTour(false));
        })();
    </script>
@endif
