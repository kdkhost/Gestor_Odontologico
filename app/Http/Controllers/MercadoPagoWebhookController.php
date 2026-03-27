<?php

namespace App\Http\Controllers;

use App\Models\PaymentInstallment;
use App\Models\PaymentTransaction;
use App\Services\WebhookProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookProcessingService $webhooks): JsonResponse
    {
        $secret = config('services.mercadopago.webhook_secret');
        $provided = $request->header('x-clinic-signature', $request->query('secret'));

        if (filled($secret) && ! hash_equals((string) $secret, (string) $provided)) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $payload = $request->json()->all() ?: $request->all();
        $externalId = (string) (data_get($payload, 'data.id') ?? data_get($payload, 'id') ?? '');
        $reference = (string) (data_get($payload, 'external_reference') ?? data_get($payload, 'data.external_reference') ?? '');
        $status = (string) (data_get($payload, 'status') ?? data_get($payload, 'data.status') ?? 'received');
        $eventName = (string) (data_get($payload, 'type') ?? 'payment');

        ['log' => $log, 'already_processed' => $alreadyProcessed] = $webhooks->capture(
            provider: 'mercadopago',
            eventName: $eventName,
            payload: $payload,
            externalId: $externalId ?: null,
            signature: $provided,
        );

        if ($alreadyProcessed) {
            return response()->json(['message' => 'Webhook já processado anteriormente.']);
        }

        try {
            DB::transaction(function () use ($reference, $externalId, $status, $payload, $log, $webhooks) {
                $installment = PaymentInstallment::query()
                    ->with(['accountReceivable.installments'])
                    ->when(filled($reference), fn ($query) => $query->where('external_reference', $reference))
                    ->first();

                if (! $installment) {
                    $webhooks->markIgnored($log, 'Nenhuma parcela encontrada para a referência informada.');

                    return;
                }

                PaymentTransaction::query()->updateOrCreate(
                    ['external_id' => $externalId ?: "mp-hook-{$log->id}"],
                    [
                        'account_receivable_id' => $installment->account_receivable_id,
                        'payment_installment_id' => $installment->id,
                        'gateway' => 'mercadopago',
                        'transaction_type' => 'webhook',
                        'payment_method' => (string) (data_get($payload, 'payment_type_id') ?? data_get($payload, 'payment_method_id') ?? ''),
                        'status' => $status,
                        'amount' => (float) (data_get($payload, 'transaction_amount') ?? $installment->amount),
                        'payload' => $payload,
                        'processed_at' => now(),
                    ],
                );

                $this->syncInstallmentStatus($installment, $status);
                $this->syncAccountStatus($installment->accountReceivable);

                $webhooks->markProcessed($log);
            });
        } catch (Throwable $exception) {
            $webhooks->markFailed($log, $exception->getMessage());

            throw $exception;
        }

        return response()->json(['message' => 'Webhook processado.']);
    }

    private function syncInstallmentStatus(PaymentInstallment $installment, string $gatewayStatus): void
    {
        $normalized = strtolower($gatewayStatus);

        if (in_array($normalized, ['approved', 'paid'], true)) {
            $installment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'balance' => 0,
            ]);

            return;
        }

        if (in_array($normalized, ['cancelled', 'canceled', 'refunded', 'charged_back'], true)) {
            $installment->update([
                'status' => 'cancelled',
                'paid_at' => null,
            ]);

            return;
        }

        $installment->update([
            'status' => optional($installment->due_date)?->isPast() ? 'overdue' : 'open',
            'paid_at' => null,
            'balance' => $installment->amount,
        ]);
    }

    private function syncAccountStatus($account): void
    {
        if (! $account) {
            return;
        }

        $account->load('installments');
        $installments = $account->installments;

        $hasOpen = $installments->whereIn('status', ['open', 'overdue'])->isNotEmpty();
        $hasPaid = $installments->where('status', 'paid')->isNotEmpty();
        $allCancelled = $installments->isNotEmpty() && $installments->every(fn ($installment) => $installment->status === 'cancelled');

        $status = match (true) {
            $allCancelled => 'cancelled',
            ! $hasOpen && $hasPaid => 'paid',
            $hasOpen && $hasPaid => 'partial',
            $installments->where('status', 'overdue')->isNotEmpty() => 'overdue',
            default => 'open',
        };

        $account->update([
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }
}
