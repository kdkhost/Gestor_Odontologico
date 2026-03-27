<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppDispatchLog;
use App\Services\WebhookProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class EvolutionWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookProcessingService $webhooks): JsonResponse
    {
        $secret = config('services.evolution.webhook_secret');
        $provided = $request->header('x-clinic-signature', $request->query('secret'));

        if (filled($secret) && ! hash_equals((string) $secret, (string) $provided)) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $payload = $request->json()->all() ?: $request->all();
        $eventName = (string) (data_get($payload, 'event') ?? 'message.status');
        $externalId = (string) (data_get($payload, 'data.key.id') ?? data_get($payload, 'data.message.key.id') ?? '');

        ['log' => $log, 'already_processed' => $alreadyProcessed] = $webhooks->capture(
            provider: 'evolution',
            eventName: $eventName,
            payload: $payload,
            externalId: $externalId ?: null,
            signature: $provided,
        );

        if ($alreadyProcessed) {
            return response()->json(['message' => 'Webhook já processado anteriormente.']);
        }

        try {
            $this->syncDispatchStatus($externalId, $eventName, $payload);

            $webhooks->markProcessed($log);
        } catch (Throwable $exception) {
            $webhooks->markFailed($log, $exception->getMessage());

            throw $exception;
        }

        return response()->json(['message' => 'Webhook recebido.']);
    }

    private function syncDispatchStatus(string $externalId, string $eventName, array $payload): void
    {
        if (blank($externalId)) {
            return;
        }

        $dispatchLog = WhatsAppDispatchLog::query()
            ->where('external_id', $externalId)
            ->latest('id')
            ->first();

        if (! $dispatchLog) {
            return;
        }

        $mappedStatus = $this->mapDispatchStatus($eventName, (string) (data_get($payload, 'data.status') ?? data_get($payload, 'status') ?? ''));

        if ($mappedStatus === null) {
            return;
        }

        $dispatchLog->update([
            'status' => $mappedStatus,
            'error_message' => $mappedStatus === 'failed'
                ? (string) (data_get($payload, 'data.error') ?? data_get($payload, 'error') ?? $dispatchLog->error_message)
                : null,
            'meta' => array_merge($dispatchLog->meta ?? [], [
                'last_webhook_event' => $eventName,
                'last_webhook_payload' => $payload,
                'last_webhook_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    private function mapDispatchStatus(string $eventName, string $rawStatus): ?string
    {
        $haystack = Str::lower(trim($eventName.' '.$rawStatus));

        return match (true) {
            Str::contains($haystack, 'read') => 'read',
            Str::contains($haystack, 'deliver') => 'delivered',
            Str::contains($haystack, 'sent') => 'sent',
            Str::contains($haystack, ['fail', 'error', 'reject']) => 'failed',
            default => null,
        };
    }
}
