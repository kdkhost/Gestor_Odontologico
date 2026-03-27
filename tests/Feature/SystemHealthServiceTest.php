<?php

namespace Tests\Feature;

use App\Models\WebhookLog;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_operational_health_snapshot(): void
    {
        $this->markApplicationAsInstalled();

        config()->set('services.mercadopago.public_key', 'pk_test');
        config()->set('services.mercadopago.access_token', 'access_test');
        config()->set('services.mercadopago.webhook_secret', 'secret_mp');
        config()->set('services.evolution.base_url', 'https://evo.local');
        config()->set('services.evolution.instance', 'instancia');
        config()->set('services.evolution.token', 'token_evo');
        config()->set('services.evolution.webhook_secret', 'secret_evo');

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{"job":"x"}',
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) str()->uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{"job":"x"}',
            'exception' => 'Falha simulada',
            'failed_at' => now(),
        ]);

        WebhookLog::query()->create([
            'provider' => 'mercadopago',
            'event_name' => 'payment',
            'external_id' => 'pay_001',
            'payload_hash' => hash('sha256', 'pay_001'),
            'status' => 'processed',
            'attempts' => 2,
            'first_received_at' => now()->subMinutes(5),
            'last_received_at' => now()->subMinute(),
            'processed_at' => now()->subMinute(),
            'payload' => ['id' => 'pay_001'],
        ]);

        WebhookLog::query()->create([
            'provider' => 'evolution',
            'event_name' => 'messages.update',
            'external_id' => 'msg_001',
            'payload_hash' => hash('sha256', 'msg_001'),
            'status' => 'failed',
            'attempts' => 1,
            'first_received_at' => now()->subMinutes(3),
            'last_received_at' => now()->subMinutes(2),
            'failed_at' => now()->subMinutes(2),
            'error_message' => 'Timeout',
            'payload' => ['id' => 'msg_001'],
        ]);

        $snapshot = app(SystemHealthService::class)->snapshot();

        $this->assertTrue($snapshot['environment']['installed']);
        $this->assertSame(1, $snapshot['infrastructure']['queue_jobs_pending']);
        $this->assertSame(1, $snapshot['infrastructure']['queue_failed_jobs']);
        $this->assertTrue($snapshot['integrations']['mercadopago']['configured']);
        $this->assertTrue($snapshot['integrations']['evolution']['configured']);
        $this->assertSame('processed', $snapshot['webhooks']['mercadopago']['status']);
        $this->assertSame(2, $snapshot['webhooks']['mercadopago']['attempts']);
        $this->assertSame('failed', $snapshot['webhooks']['evolution']['status']);
        $this->assertCount(1, $snapshot['webhooks']['recent_failures']);
    }
}
