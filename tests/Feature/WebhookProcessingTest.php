<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\PaymentInstallment;
use App\Models\Unit;
use App\Models\WebhookLog;
use App\Models\WhatsAppDispatchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_mercadopago_webhook_is_idempotent_and_updates_attempts(): void
    {
        $this->markApplicationAsInstalled();
        config()->set('services.mercadopago.webhook_secret', 'segredo-mp');

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Marina Costa',
            'is_active' => true,
        ]);

        $receivable = AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'reference' => 'REC-001',
            'description' => 'Plano clínico',
            'status' => 'open',
            'total_amount' => 300,
            'net_amount' => 300,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        PaymentInstallment::query()->create([
            'account_receivable_id' => $receivable->id,
            'installment_number' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'amount' => 300,
            'balance' => 300,
            'status' => 'open',
            'external_reference' => 'MP-REC-001',
        ]);

        $payload = [
            'type' => 'payment',
            'id' => 'pay_123',
            'external_reference' => 'MP-REC-001',
            'status' => 'approved',
            'transaction_amount' => 300,
            'payment_method_id' => 'pix',
        ];

        $this->postJson(route('webhooks.mercadopago', ['secret' => 'segredo-mp']), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Webhook processado.');

        $this->postJson(route('webhooks.mercadopago', ['secret' => 'segredo-mp']), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Webhook já processado anteriormente.');

        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseHas('payment_transactions', [
            'external_id' => 'pay_123',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('payment_installments', [
            'external_reference' => 'MP-REC-001',
            'status' => 'paid',
            'balance' => 0,
        ]);
        $this->assertDatabaseHas('accounts_receivable', [
            'reference' => 'REC-001',
            'status' => 'paid',
        ]);
        $this->assertSame(2, WebhookLog::query()->where('provider', 'mercadopago')->value('attempts'));
        $this->assertSame('processed', WebhookLog::query()->where('provider', 'mercadopago')->value('status'));
    }

    public function test_evolution_webhook_updates_dispatch_log_delivery_status(): void
    {
        $this->markApplicationAsInstalled();
        config()->set('services.evolution.webhook_secret', 'segredo-evo');

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Helena Dias',
            'whatsapp' => '11999990000',
            'is_active' => true,
        ]);

        $template = NotificationTemplate::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Lembrete',
            'channel' => 'whatsapp',
            'provider' => 'evolution',
            'trigger_event' => 'appointment.reminder',
            'message' => 'Olá',
            'cooldown_seconds' => 120,
            'hourly_limit_per_recipient' => 4,
            'requires_opt_in' => false,
            'requires_official_window' => false,
            'is_active' => true,
        ]);

        WhatsAppDispatchLog::query()->create([
            'notification_template_id' => $template->id,
            'unit_id' => $unit->id,
            'recipient_phone' => $patient->whatsapp,
            'trigger_event' => 'appointment.reminder',
            'provider' => 'evolution',
            'status' => 'sent',
            'external_id' => 'msg-001',
            'attempted_at' => now()->subMinute(),
            'sent_at' => now()->subMinute(),
        ]);

        $payload = [
            'event' => 'messages.update',
            'data' => [
                'status' => 'READ',
                'key' => [
                    'id' => 'msg-001',
                ],
            ],
        ];

        $this->postJson(route('webhooks.evolution', ['secret' => 'segredo-evo']), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Webhook recebido.');

        $this->assertDatabaseHas('whatsapp_dispatch_logs', [
            'external_id' => 'msg-001',
            'status' => 'read',
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'evolution',
            'external_id' => 'msg-001',
            'status' => 'processed',
        ]);
    }
}
