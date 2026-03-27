<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\NotificationTemplate;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $actions = ['view', 'create', 'update', 'delete', 'approve', 'export', 'manage'];

        foreach (config('clinic.modules', []) as $module) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }

        foreach (config('clinic.roles', []) as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        Role::findByName('superadmin', 'web')->syncPermissions(Permission::all());

        Role::findByName('admin-unidade', 'web')->syncPermissions(
            Permission::query()
                ->whereNotIn('name', ['configuracoes.delete'])
                ->get(),
        );

        Role::findByName('recepcao', 'web')->syncPermissions([
            'dashboard.view',
            'pacientes.view',
            'pacientes.create',
            'pacientes.update',
            'agenda.view',
            'agenda.create',
            'agenda.update',
            'agenda.approve',
            'documentos.view',
            'documentos.create',
            'notificacoes.view',
            'notificacoes.manage',
        ]);

        Role::findByName('dentista', 'web')->syncPermissions([
            'dashboard.view',
            'pacientes.view',
            'pacientes.update',
            'agenda.view',
            'agenda.update',
            'procedimentos.view',
            'planos.view',
            'planos.update',
            'documentos.view',
        ]);

        Role::findByName('financeiro', 'web')->syncPermissions([
            'dashboard.view',
            'financeiro.view',
            'financeiro.create',
            'financeiro.update',
            'financeiro.export',
            'planos.view',
            'planos.approve',
            'pacientes.view',
            'notificacoes.view',
            'notificacoes.manage',
        ]);

        Role::findByName('estoque', 'web')->syncPermissions([
            'dashboard.view',
            'estoque.view',
            'estoque.create',
            'estoque.update',
            'estoque.export',
            'procedimentos.view',
            'procedimentos.update',
        ]);

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'branding', 'key' => 'app_name'],
            ['type' => 'string', 'value' => config('app.name'), 'is_public' => true],
        );

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'branding', 'key' => 'system_version'],
            ['type' => 'string', 'value' => config('clinic.system_version'), 'is_public' => true],
        );

        foreach (config('clinic.developer', []) as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['unit_id' => null, 'group' => 'developer', 'key' => $key],
                ['type' => 'string', 'value' => (string) $value, 'is_public' => false],
            );
        }

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'portal', 'key' => 'allow_registration'],
            ['type' => 'boolean', 'value' => '1', 'is_public' => true],
        );

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'maintenance', 'key' => 'enabled'],
            ['type' => 'boolean', 'value' => '0', 'is_public' => false],
        );

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'maintenance', 'key' => 'release_at'],
            ['type' => 'string', 'value' => now()->addHours(2)->toDateTimeString(), 'is_public' => false],
        );

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'onboarding', 'key' => 'auto_start'],
            ['type' => 'boolean', 'value' => config('clinic.onboarding.auto_start') ? '1' : '0', 'is_public' => false],
        );

        foreach (config('clinic.whatsapp', []) as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                default => 'string',
            };

            SystemSetting::query()->updateOrCreate(
                ['unit_id' => null, 'group' => 'whatsapp', 'key' => $key],
                ['type' => $type, 'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'is_public' => false],
            );
        }

        foreach (config('clinic.automation', []) as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                default => 'string',
            };

            SystemSetting::query()->updateOrCreate(
                ['unit_id' => null, 'group' => 'automation', 'key' => $key],
                ['type' => $type, 'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'is_public' => false],
            );
        }

        DocumentTemplate::query()->updateOrCreate(
            ['slug' => 'termo-consentimento-inicial'],
            [
                'name' => 'Termo de Consentimento Inicial',
                'category' => 'consentimento',
                'channel' => 'portal',
                'variables' => ['paciente_nome', 'profissional_nome', 'data_atendimento'],
                'body' => '<h2>Consentimento de Atendimento</h2><p>Eu, {{paciente_nome}}, autorizo o atendimento odontológico inicial em {{data_atendimento}}.</p>',
                'is_active' => true,
            ],
        );

        NotificationTemplate::query()->updateOrCreate(
            ['name' => 'Confirmacao de Consulta'],
            [
                'channel' => 'whatsapp',
                'provider' => 'evolution',
                'trigger_event' => 'appointment.confirmed',
                'subject' => 'Consulta confirmada',
                'variables' => ['saudacao', 'paciente_nome', 'data_consulta', 'hora_consulta', 'unidade_nome'],
                'message' => '{{saudacao}}, {{paciente_nome}}. Sua consulta foi confirmada para {{data_consulta}} às {{hora_consulta}} na unidade {{unidade_nome}}.',
                'delivery_window_start' => '08:00:00',
                'delivery_window_end' => '18:00:00',
                'cooldown_seconds' => 180,
                'hourly_limit_per_recipient' => 3,
                'requires_opt_in' => true,
                'requires_official_window' => true,
                'meta' => ['category' => 'utility'],
                'is_active' => true,
            ],
        );

        NotificationTemplate::query()->updateOrCreate(
            ['name' => 'Lembrete de Parcela'],
            [
                'channel' => 'whatsapp',
                'provider' => 'evolution',
                'trigger_event' => 'financial.installment_due',
                'subject' => 'Parcela em aberto',
                'variables' => ['saudacao', 'paciente_nome', 'valor', 'vencimento'],
                'message' => '{{saudacao}}, {{paciente_nome}}. Identificamos uma parcela no valor de {{valor}} com vencimento em {{vencimento}}.',
                'delivery_window_start' => '08:00:00',
                'delivery_window_end' => '18:00:00',
                'cooldown_seconds' => 300,
                'hourly_limit_per_recipient' => 2,
                'requires_opt_in' => true,
                'requires_official_window' => true,
                'meta' => ['category' => 'utility'],
                'is_active' => true,
            ],
        );

        NotificationTemplate::query()->updateOrCreate(
            ['name' => 'Lembrete de Consulta'],
            [
                'channel' => 'whatsapp',
                'provider' => 'evolution',
                'trigger_event' => 'appointment.reminder',
                'subject' => 'Lembrete de consulta',
                'variables' => ['saudacao', 'paciente_nome', 'data_consulta', 'hora_consulta', 'unidade_nome', 'procedimento_nome'],
                'message' => "{{saudacao}}, {{paciente_nome}}.\n\nEste e um lembrete da sua consulta em {{data_consulta}} as {{hora_consulta}} na unidade {{unidade_nome}} para {{procedimento_nome}}.\n\nSe precisar ajustar o horario, responda esta mensagem dentro da janela de atendimento.",
                'delivery_window_start' => '08:00:00',
                'delivery_window_end' => '18:00:00',
                'cooldown_seconds' => 300,
                'hourly_limit_per_recipient' => 2,
                'requires_opt_in' => true,
                'requires_official_window' => true,
                'meta' => ['category' => 'utility'],
                'is_active' => true,
            ],
        );

        NotificationTemplate::query()->updateOrCreate(
            ['name' => 'Reativacao de Paciente'],
            [
                'channel' => 'whatsapp',
                'provider' => 'evolution',
                'trigger_event' => 'patient.reactivation',
                'subject' => 'Retorno preventivo',
                'variables' => ['saudacao', 'paciente_nome', 'unidade_nome', 'ultima_visita'],
                'message' => "{{saudacao}}, {{paciente_nome}}.\n\nNotamos que sua ultima visita na unidade {{unidade_nome}} foi em {{ultima_visita}}. Se desejar, podemos organizar um novo horario para acompanhamento preventivo.",
                'delivery_window_start' => '09:00:00',
                'delivery_window_end' => '17:30:00',
                'cooldown_seconds' => 600,
                'hourly_limit_per_recipient' => 1,
                'requires_opt_in' => true,
                'requires_official_window' => true,
                'meta' => ['category' => 'utility'],
                'is_active' => true,
            ],
        );
    }
}
