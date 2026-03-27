<?php

namespace App\Services;

use App\Models\WebhookLog;
use App\Models\WhatsAppDispatchLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    public function __construct(
        private readonly InstallerService $installer,
    ) {}

    public function snapshot(): array
    {
        return [
            'environment' => [
                'app_name' => config('app.name'),
                'app_env' => config('app.env'),
                'app_url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'php_version' => PHP_VERSION,
                'db_connection' => config('database.default'),
                'session_driver' => config('session.driver'),
                'cache_store' => config('cache.default'),
                'queue_connection' => config('queue.default'),
                'installed' => $this->installer->isInstalled(),
                'requirements' => $this->installer->requirements(),
            ],
            'infrastructure' => [
                'storage_writable' => is_writable(storage_path()),
                'cache_writable' => is_writable(base_path('bootstrap/cache')),
                'public_storage_link' => File::exists(public_path('storage')),
                'queue_jobs_pending' => $this->countTableRows('jobs'),
                'queue_failed_jobs' => $this->countTableRows('failed_jobs'),
            ],
            'integrations' => [
                'mercadopago' => [
                    'configured' => filled(config('services.mercadopago.public_key')) && filled(config('services.mercadopago.access_token')),
                    'webhook_secret' => filled(config('services.mercadopago.webhook_secret')),
                ],
                'evolution' => [
                    'configured' => filled(config('services.evolution.base_url')) && filled(config('services.evolution.instance')) && filled(config('services.evolution.token')),
                    'webhook_secret' => filled(config('services.evolution.webhook_secret')),
                ],
                'webpush' => [
                    'configured' => filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key')),
                ],
            ],
            'webhooks' => [
                'mercadopago' => $this->latestWebhook('mercadopago'),
                'evolution' => $this->latestWebhook('evolution'),
                'recent_failures' => $this->recentFailures(),
            ],
            'messaging' => [
                'sent_last_7d' => WhatsAppDispatchLog::query()->where('created_at', '>=', now()->subDays(7))->where('status', 'sent')->count(),
                'blocked_last_7d' => WhatsAppDispatchLog::query()->where('created_at', '>=', now()->subDays(7))->where('status', 'blocked')->count(),
                'failed_last_7d' => WhatsAppDispatchLog::query()->where('created_at', '>=', now()->subDays(7))->where('status', 'failed')->count(),
            ],
        ];
    }

    private function countTableRows(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function latestWebhook(string $provider): array
    {
        $record = WebhookLog::query()
            ->where('provider', $provider)
            ->latest('last_received_at')
            ->latest('created_at')
            ->first();

        if (! $record) {
            return [
                'status' => 'never_received',
                'label' => 'Nunca recebido',
                'attempts' => 0,
                'last_received_at' => null,
                'processed_at' => null,
                'error_message' => null,
            ];
        }

        return [
            'status' => $record->status,
            'label' => match ($record->status) {
                'processed' => 'Processado',
                'ignored' => 'Ignorado',
                'failed' => 'Falhou',
                default => 'Recebido',
            },
            'attempts' => (int) ($record->attempts ?? 1),
            'last_received_at' => $record->last_received_at,
            'processed_at' => $record->processed_at,
            'error_message' => $record->error_message,
        ];
    }

    private function recentFailures(): array
    {
        return WebhookLog::query()
            ->where('status', 'failed')
            ->latest('failed_at')
            ->limit(5)
            ->get()
            ->map(fn (WebhookLog $log) => [
                'provider' => $log->provider,
                'event_name' => $log->event_name,
                'external_id' => $log->external_id,
                'error_message' => $log->error_message,
                'failed_at' => $log->failed_at,
            ])
            ->all();
    }
}
