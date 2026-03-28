<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AdminModuleRegistry
{
    public static function all(): array
    {
        return [
            [
                'slug' => 'central-operacional',
                'group' => 'Operacao',
                'label' => 'Central operacional',
                'description' => 'Leitura gerencial da operacao diaria, gargalos, no-show e agenda pendente.',
                'icon' => 'fas fa-chart-line',
                'color' => 'info',
                'permission' => 'dashboard.view',
                'iframe_url' => static::coreUrl('operational-center'),
            ],
            [
                'slug' => 'agenda-visual',
                'group' => 'Operacao',
                'label' => 'Agenda visual',
                'description' => 'Visao por unidade, profissional e cadeira com filtros e carga operacional.',
                'icon' => 'far fa-calendar-alt',
                'color' => 'primary',
                'permission' => 'agenda.view',
                'iframe_url' => static::coreUrl('visual-agenda'),
            ],
            [
                'slug' => 'agenda',
                'group' => 'Operacao',
                'label' => 'Agenda administrativa',
                'description' => 'Gestao detalhada de agendamentos, status, encaixes e atendimento.',
                'icon' => 'fas fa-calendar-check',
                'color' => 'primary',
                'permission' => 'agenda.view',
                'route_name' => 'admin.appointments.index',
                'route_parameters' => [],
                'active_patterns' => ['admin/agenda*', 'admin/modulos/agenda*'],
                'iframe_url' => static::coreUrl('appointments'),
            ],
            [
                'slug' => 'pacientes',
                'group' => 'Operacao',
                'label' => 'Pacientes',
                'description' => 'Cadastro, perfil 360, historico, contatos e dados sensiveis do paciente.',
                'icon' => 'fas fa-user-injured',
                'color' => 'teal',
                'permission' => 'pacientes.view',
                'route_name' => 'admin.patients.index',
                'route_parameters' => [],
                'active_patterns' => ['admin/pacientes*', 'admin/modulos/pacientes*'],
                'iframe_url' => static::coreUrl('patients'),
            ],
            [
                'slug' => 'planos',
                'group' => 'Operacao',
                'label' => 'Planos de tratamento',
                'description' => 'Controle clinico e comercial dos planos e itens executaveis.',
                'icon' => 'fas fa-notes-medical',
                'color' => 'teal',
                'permission' => 'planos.view',
                'iframe_url' => static::coreUrl('treatment-plans'),
            ],
            [
                'slug' => 'governanca-clinica',
                'group' => 'Operacao',
                'label' => 'Governanca clinica',
                'description' => 'Pendencias de prontuario, retornos futuros e acompanhamento assistencial.',
                'icon' => 'fas fa-stethoscope',
                'color' => 'teal',
                'permission' => 'dashboard.view',
                'iframe_url' => static::coreUrl('clinical-governance-center'),
            ],
            [
                'slug' => 'financeiro',
                'group' => 'Financeiro',
                'label' => 'Contas a receber',
                'description' => 'Cobrancas, inadimplencia, baixas, parcelas e carteira da clinica.',
                'icon' => 'fas fa-wallet',
                'color' => 'success',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('account-receivables'),
            ],
            [
                'slug' => 'bi',
                'group' => 'Financeiro',
                'label' => 'BI gerencial',
                'description' => 'Receita, metas, ticket medio, ranking e exportacoes gerenciais.',
                'icon' => 'fas fa-chart-pie',
                'color' => 'success',
                'permission' => 'dashboard.view',
                'iframe_url' => static::coreUrl('business-intelligence-center'),
            ],
            [
                'slug' => 'repasses',
                'group' => 'Financeiro',
                'label' => 'Repasse e comissao',
                'description' => 'Fechamento de lotes, comprovantes, conciliacao e pagamento ao profissional.',
                'icon' => 'fas fa-hand-holding-usd',
                'color' => 'olive',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('commission-settlement-center'),
            ],
            [
                'slug' => 'conciliacao-bancaria',
                'group' => 'Financeiro',
                'label' => 'Conciliacao bancaria',
                'description' => 'Importacao de extrato CSV, TXT e OFX com sugestoes assistidas.',
                'icon' => 'fas fa-file-invoice-dollar',
                'color' => 'olive',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('statement-reconciliation-center'),
            ],
            [
                'slug' => 'fiscal',
                'group' => 'Financeiro',
                'label' => 'Fiscal NFSe',
                'description' => 'Fila fiscal, notas emitidas, rascunhos e protocolo municipal.',
                'icon' => 'fas fa-receipt',
                'color' => 'warning',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('fiscal-billing-center'),
            ],
            [
                'slug' => 'estoque',
                'group' => 'Apoio',
                'label' => 'Estoque',
                'description' => 'Itens, categorias, consumo por procedimento, lotes e validade.',
                'icon' => 'fas fa-boxes',
                'color' => 'warning',
                'permission' => 'estoque.view',
                'iframe_url' => static::coreUrl('inventory-items'),
            ],
            [
                'slug' => 'notificacoes',
                'group' => 'Apoio',
                'label' => 'Templates e notificacoes',
                'description' => 'Mensagens operacionais, variaveis e modelos de comunicacao.',
                'icon' => 'fab fa-whatsapp',
                'color' => 'lime',
                'permission' => 'notificacoes.view',
                'iframe_url' => static::coreUrl('notification-templates'),
            ],
            [
                'slug' => 'automacoes',
                'group' => 'Apoio',
                'label' => 'Automacoes',
                'description' => 'Regua automatica de lembrete, cobranca preventiva e reativacao.',
                'icon' => 'fas fa-robot',
                'color' => 'lime',
                'permission' => 'notificacoes.view',
                'iframe_url' => static::coreUrl('automation-center'),
            ],
            [
                'slug' => 'documentos',
                'group' => 'Apoio',
                'label' => 'Documentos',
                'description' => 'Modelos editaveis, aceite digital e rastreabilidade documental.',
                'icon' => 'fas fa-file-signature',
                'color' => 'secondary',
                'permission' => 'documentos.view',
                'iframe_url' => static::coreUrl('document-templates'),
            ],
            [
                'slug' => 'profissionais',
                'group' => 'Apoio',
                'label' => 'Profissionais',
                'description' => 'Equipe clinica, especialidades e vinculacao operacional.',
                'icon' => 'fas fa-user-md',
                'color' => 'secondary',
                'permission' => 'profissionais.view',
                'iframe_url' => static::coreUrl('professionals'),
            ],
            [
                'slug' => 'procedimentos',
                'group' => 'Apoio',
                'label' => 'Procedimentos',
                'description' => 'Catalogo clinico e financeiro dos procedimentos realizados.',
                'icon' => 'fas fa-tooth',
                'color' => 'secondary',
                'permission' => 'procedimentos.view',
                'iframe_url' => static::coreUrl('procedures'),
            ],
            [
                'slug' => 'convenios',
                'group' => 'Convenios',
                'label' => 'Planos de convenio',
                'description' => 'Regras internas, cobertura e cadastros de operadoras e planos.',
                'icon' => 'fas fa-id-card-alt',
                'color' => 'purple',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('insurance-plans'),
            ],
            [
                'slug' => 'autorizacoes-convenio',
                'group' => 'Convenios',
                'label' => 'Autorizacoes',
                'description' => 'Guias, retorno da operadora, validade e negativas.',
                'icon' => 'fas fa-stamp',
                'color' => 'purple',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('insurance-authorization-center'),
            ],
            [
                'slug' => 'faturamento-convenio',
                'group' => 'Convenios',
                'label' => 'Faturamento TISS-ready',
                'description' => 'Lotes, glosas, reapresentacao e exportacao estruturada de faturamento.',
                'icon' => 'fas fa-file-medical-alt',
                'color' => 'purple',
                'permission' => 'financeiro.view',
                'iframe_url' => static::coreUrl('insurance-claim-billing-center'),
            ],
            [
                'slug' => 'usuarios',
                'group' => 'Administracao',
                'label' => 'Usuarios',
                'description' => 'Acessos, papeis internos e segregacao administrativa por unidade.',
                'icon' => 'fas fa-users-cog',
                'color' => 'danger',
                'permission' => 'usuarios.view',
                'iframe_url' => static::coreUrl('users'),
            ],
            [
                'slug' => 'unidades',
                'group' => 'Administracao',
                'label' => 'Unidades',
                'description' => 'Dados operacionais, fiscal, agenda e parametros por unidade.',
                'icon' => 'fas fa-clinic-medical',
                'color' => 'danger',
                'permission' => 'unidades.view',
                'iframe_url' => static::coreUrl('units'),
            ],
            [
                'slug' => 'saude-do-sistema',
                'group' => 'Administracao',
                'label' => 'Saude do sistema',
                'description' => 'Infraestrutura, webhooks, filas, storage e integracoes.',
                'icon' => 'fas fa-heartbeat',
                'color' => 'danger',
                'permission' => 'configuracoes.view',
                'iframe_url' => static::coreUrl('system-health'),
            ],
            [
                'slug' => 'lgpd',
                'group' => 'Administracao',
                'label' => 'Central LGPD',
                'description' => 'Exportacao e anonimizacao estruturada dos dados do paciente.',
                'icon' => 'fas fa-user-shield',
                'color' => 'danger',
                'permission' => 'configuracoes.view',
                'iframe_url' => static::coreUrl('privacy-management-center'),
            ],
            [
                'slug' => 'configuracoes',
                'group' => 'Administracao',
                'label' => 'Configuracoes',
                'description' => 'Marca, versao, tour, seguranca do WhatsApp e parametros globais.',
                'icon' => 'fas fa-cogs',
                'color' => 'danger',
                'permission' => 'configuracoes.view',
                'iframe_url' => static::coreUrl('system-settings'),
            ],
        ];
    }

    public static function visibleFor(?User $user): array
    {
        return array_values(array_filter(static::all(), fn (array $module): bool => static::canAccess($user, $module)));
    }

    public static function groupedFor(?User $user): Collection
    {
        return collect(static::visibleFor($user))
            ->groupBy('group');
    }

    public static function find(string $slug, ?User $user = null): ?array
    {
        foreach (static::visibleFor($user) as $module) {
            if ($module['slug'] === $slug) {
                return $module;
            }
        }

        return null;
    }

    public static function findAny(string $slug): ?array
    {
        foreach (static::all() as $module) {
            if ($module['slug'] === $slug) {
                return $module;
            }
        }

        return null;
    }

    public static function routeName(array $module): string
    {
        return $module['route_name'] ?? 'admin.workspace';
    }

    public static function routeParameters(array $module): array
    {
        return $module['route_parameters'] ?? ['slug' => $module['slug']];
    }

    public static function isEmbedded(array $module): bool
    {
        return ! isset($module['route_name']) || $module['route_name'] === 'admin.workspace';
    }

    public static function menu(): array
    {
        return [
            [
                'text' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon' => 'fas fa-gauge-high',
                'active' => ['admin', 'admin/'],
            ],
            ['header' => 'Operacao'],
            static::menuItem('central-operacional', 'dashboard.view'),
            static::menuItem('agenda-visual', 'agenda.view'),
            static::menuItem('agenda', 'agenda.view'),
            static::menuItem('pacientes', 'pacientes.view'),
            static::menuItem('planos', 'planos.view'),
            static::menuItem('governanca-clinica', 'dashboard.view'),
            ['header' => 'Financeiro'],
            static::menuItem('financeiro', 'financeiro.view'),
            static::menuItem('bi', 'dashboard.view'),
            static::menuItem('repasses', 'financeiro.view'),
            static::menuItem('conciliacao-bancaria', 'financeiro.view'),
            static::menuItem('fiscal', 'financeiro.view'),
            ['header' => 'Apoio'],
            static::menuItem('estoque', 'estoque.view'),
            static::menuItem('notificacoes', 'notificacoes.view'),
            static::menuItem('automacoes', 'notificacoes.view'),
            static::menuItem('documentos', 'documentos.view'),
            static::menuItem('profissionais', 'profissionais.view'),
            static::menuItem('procedimentos', 'procedimentos.view'),
            ['header' => 'Convenios'],
            static::menuItem('convenios', 'financeiro.view'),
            static::menuItem('autorizacoes-convenio', 'financeiro.view'),
            static::menuItem('faturamento-convenio', 'financeiro.view'),
            ['header' => 'Administracao'],
            static::menuItem('usuarios', 'usuarios.view'),
            static::menuItem('unidades', 'unidades.view'),
            static::menuItem('saude-do-sistema', 'configuracoes.view'),
            static::menuItem('lgpd', 'configuracoes.view'),
            static::menuItem('configuracoes', 'configuracoes.view'),
        ];
    }

    private static function menuItem(string $slug, string|array|null $permission = null): array
    {
        $module = collect(static::all())->firstWhere('slug', $slug);
        $activePatterns = $module['active_patterns'] ?? ["admin/modulos/{$slug}*"];

        return [
            'text' => $module['label'],
            'route' => [static::routeName($module), static::routeParameters($module)],
            'icon' => $module['icon'],
            'can' => $permission,
            'active' => $activePatterns,
        ];
    }

    private static function canAccess(?User $user, array $module): bool
    {
        if (! $user || ! $user->canAccessAdminArea()) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return true;
        }

        $permissions = Arr::wrap($module['permission'] ?? []);

        if ($permissions === []) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private static function coreUrl(string $path): string
    {
        return '/admin/core/'.ltrim($path, '/');
    }
}
