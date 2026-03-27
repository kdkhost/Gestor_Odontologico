<?php

namespace Tests\Feature;

use App\Models\NotificationTemplate;
use App\Models\WhatsAppDispatchLog;
use App\Services\SettingService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_whatsapp_text_and_appends_signature(): void
    {
        $service = app(WhatsAppTemplateService::class);
        app(SettingService::class)->put('whatsapp', 'signature', 'Equipe {{app_name}}');

        $template = NotificationTemplate::query()->create([
            'name' => 'Confirmação',
            'channel' => 'whatsapp',
            'provider' => 'evolution',
            'trigger_event' => 'appointment.confirmed',
            'message' => '<p>Olá {{paciente_nome}}</p><p>Sua consulta está confirmada.</p>',
            'cooldown_seconds' => 120,
            'hourly_limit_per_recipient' => 4,
            'requires_opt_in' => true,
            'requires_official_window' => false,
            'is_active' => true,
        ]);

        $message = $service->render($template, ['paciente_nome' => 'Maria']);

        $this->assertStringContainsString('Olá Maria', $message);
        $this->assertStringContainsString('Equipe Odonto Flow', $message);
        $this->assertStringNotContainsString('<p>', $message);
    }

    public function test_it_blocks_send_when_cooldown_is_not_respected(): void
    {
        $service = app(WhatsAppTemplateService::class);
        $settings = app(SettingService::class);

        $settings->put('whatsapp', 'dispatch_enabled', true, 'boolean');
        $settings->put('whatsapp', 'respect_business_hours', false, 'boolean');
        $settings->put('whatsapp', 'require_opt_in', false, 'boolean');

        $template = NotificationTemplate::query()->create([
            'name' => 'Lembrete',
            'channel' => 'whatsapp',
            'provider' => 'evolution',
            'trigger_event' => 'financial.installment_due',
            'message' => 'Parcela em aberto.',
            'cooldown_seconds' => 300,
            'hourly_limit_per_recipient' => 4,
            'requires_opt_in' => false,
            'requires_official_window' => false,
            'is_active' => true,
        ]);

        WhatsAppDispatchLog::query()->create([
            'notification_template_id' => $template->id,
            'recipient_phone' => '5511999990000',
            'trigger_event' => $template->trigger_event,
            'provider' => 'evolution',
            'status' => 'sent',
            'attempted_at' => now(),
            'sent_at' => now(),
        ]);

        $guard = $service->guard($template, '(11) 99999-0000', ['opt_in_confirmed' => true]);

        $this->assertFalse($guard['allowed']);
        $this->assertSame('Intervalo mínimo entre mensagens ainda não atingido.', $guard['reason']);
    }
}
