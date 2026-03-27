<?php

namespace App\Services;

use App\Models\WebhookLog;

class WebhookProcessingService
{
    public function capture(string $provider, string $eventName, array $payload, ?string $externalId = null, ?string $signature = null): array
    {
        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $query = WebhookLog::query()
            ->where('provider', $provider)
            ->where('event_name', $eventName);

        if (filled($externalId)) {
            $query->where('external_id', $externalId);
        } else {
            $query->where('payload_hash', $payloadHash);
        }

        $log = $query->first();

        if ($log) {
            $alreadyProcessed = in_array($log->status, ['processed', 'ignored'], true);

            $log->update([
                'signature' => $signature ?: $log->signature,
                'payload_hash' => $payloadHash,
                'payload' => $payload,
                'attempts' => (int) $log->attempts + 1,
                'last_received_at' => now(),
            ]);

            return [
                'log' => $log->fresh(),
                'already_processed' => $alreadyProcessed,
            ];
        }

        $log = WebhookLog::query()->create([
            'provider' => $provider,
            'event_name' => $eventName,
            'external_id' => filled($externalId) ? $externalId : null,
            'signature' => $signature,
            'payload_hash' => $payloadHash,
            'status' => 'received',
            'attempts' => 1,
            'first_received_at' => now(),
            'last_received_at' => now(),
            'payload' => $payload,
        ]);

        return [
            'log' => $log,
            'already_processed' => false,
        ];
    }

    public function markProcessed(WebhookLog $log, array $extra = []): WebhookLog
    {
        $log->update([
            'status' => 'processed',
            'processed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
            ...$extra,
        ]);

        return $log->fresh();
    }

    public function markIgnored(WebhookLog $log, string $reason, array $extra = []): WebhookLog
    {
        $log->update([
            'status' => 'ignored',
            'processed_at' => now(),
            'error_message' => $reason,
            ...$extra,
        ]);

        return $log->fresh();
    }

    public function markFailed(WebhookLog $log, string $reason, array $extra = []): WebhookLog
    {
        $log->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $reason,
            ...$extra,
        ]);

        return $log->fresh();
    }
}
