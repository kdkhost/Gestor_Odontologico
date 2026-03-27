<?php

namespace App\Services;

use App\Models\AccountReceivable;
use App\Models\FiscalInvoice;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiscalInvoiceService
{
    public function providerOptions(): array
    {
        return [
            'manual' => 'Manual',
            'mock' => 'Mock / homologacao',
        ];
    }

    public function eligibleReceivables(?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): Collection
    {
        [$start, $end] = $this->resolvePeriod($fromDate, $toDate);

        return $this->scopeQuery(
            AccountReceivable::query()->with(['unit', 'patient']),
            $unitId,
        )
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->where('net_amount', '>', 0)
            ->whereDoesntHave('fiscalInvoices', function (Builder $query): void {
                $query->where('status', '!=', 'cancelled');
            })
            ->latest('paid_at')
            ->get()
            ->map(function (AccountReceivable $receivable): array {
                $missing = $this->missingFiscalFields($receivable->unit);

                return [
                    'account_receivable_id' => $receivable->id,
                    'reference' => $receivable->reference,
                    'description' => $receivable->description,
                    'patient_name' => $receivable->patient?->name ?? 'Paciente',
                    'unit_name' => $receivable->unit?->name ?? 'Sem unidade',
                    'amount' => round((float) $receivable->net_amount, 2),
                    'paid_at' => $receivable->paid_at,
                    'is_ready' => $missing === [],
                    'missing_fields' => $missing,
                ];
            });
    }

    public function recentInvoices(?int $unitId, int $limit = 15): Collection
    {
        return $this->scopeInvoiceQuery(
            FiscalInvoice::query()->with(['unit', 'patient', 'accountReceivable', 'createdBy']),
            $unitId,
        )
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function summary(?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): array
    {
        [$start, $end] = $this->resolvePeriod($fromDate, $toDate);

        $eligibleCount = $this->eligibleReceivables($unitId, $start, $end)->count();
        $eligibleAmount = $this->eligibleReceivables($unitId, $start, $end)->sum('amount');

        $draftCount = $this->scopeInvoiceQuery(FiscalInvoice::query(), $unitId)
            ->where('status', 'draft')
            ->count();

        $pendingCount = $this->scopeInvoiceQuery(FiscalInvoice::query(), $unitId)
            ->whereIn('status', ['pending_submission', 'submitted'])
            ->count();

        $issuedAmount = (float) $this->scopeInvoiceQuery(FiscalInvoice::query(), $unitId)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$start, $end])
            ->sum('amount');

        return [
            'eligible_count' => $eligibleCount,
            'eligible_amount' => round((float) $eligibleAmount, 2),
            'draft_count' => $draftCount,
            'pending_count' => $pendingCount,
            'issued_amount' => round($issuedAmount, 2),
        ];
    }

    public function createDraftForReceivable(AccountReceivable $receivable, array $payload = []): FiscalInvoice
    {
        $receivable->loadMissing(['unit', 'patient']);

        if ($receivable->status !== 'paid' || $receivable->paid_at === null) {
            throw new RuntimeException('Somente contas pagas podem gerar NFSe.');
        }

        $existing = $receivable->fiscalInvoices()
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($existing) {
            throw new RuntimeException('Ja existe uma NFSe ativa vinculada a esta conta.');
        }

        $missing = $this->missingFiscalFields($receivable->unit);

        if ($missing !== []) {
            throw new RuntimeException('A unidade nao possui dados fiscais minimos: '.implode(', ', $missing).'.');
        }

        return DB::transaction(function () use ($receivable, $payload) {
            $amount = round((float) $receivable->net_amount, 2);
            $deductions = round((float) ($payload['deductions_amount'] ?? 0), 2);
            $taxBase = round(max(0, $amount - $deductions), 2);
            $issRate = round((float) ($payload['iss_rate'] ?? $receivable->unit?->default_iss_rate ?? 0), 2);
            $issAmount = round($taxBase * ($issRate / 100), 2);

            $invoice = FiscalInvoice::query()->create([
                'unit_id' => $receivable->unit_id,
                'patient_id' => $receivable->patient_id,
                'account_receivable_id' => $receivable->id,
                'created_by_user_id' => $payload['created_by_user_id'] ?? null,
                'reference' => $this->generateReference($receivable->id),
                'provider_profile' => $payload['provider_profile'] ?? $receivable->unit?->nfse_provider_profile ?? 'manual',
                'city_code' => $payload['city_code'] ?? $receivable->unit?->service_city_code,
                'service_code' => $payload['service_code'] ?? $receivable->unit?->default_service_code,
                'service_description' => $payload['service_description'] ?? $receivable->description,
                'status' => 'draft',
                'amount' => $amount,
                'deductions_amount' => $deductions,
                'tax_base_amount' => $taxBase,
                'iss_rate' => $issRate,
                'iss_amount' => $issAmount,
                'rps_series' => $payload['rps_series'] ?? $receivable->unit?->rps_series,
                'customer_snapshot' => [
                    'name' => $receivable->patient?->name,
                    'document' => $receivable->patient?->cpf,
                    'email' => $receivable->patient?->email,
                    'phone' => $receivable->patient?->phone,
                    'city' => $receivable->patient?->city,
                    'state' => $receivable->patient?->state,
                ],
                'provider_payload' => [
                    'unit_document' => $receivable->unit?->document,
                    'unit_municipal_registration' => $receivable->unit?->municipal_registration,
                    'service_city_code' => $receivable->unit?->service_city_code,
                    'cnae_code' => $receivable->unit?->cnae_code,
                ],
            ]);

            return $invoice->load(['unit', 'patient', 'accountReceivable', 'createdBy']);
        });
    }

    public function createDraftsForEligible(?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate, ?int $createdByUserId = null): int
    {
        $count = 0;

        $eligible = $this->eligibleReceivables($unitId, $fromDate, $toDate)
            ->filter(fn (array $candidate): bool => $candidate['is_ready'] === true);

        foreach ($eligible as $candidate) {
            $this->createDraftForReceivable(
                receivable: AccountReceivable::query()->findOrFail($candidate['account_receivable_id']),
                payload: ['created_by_user_id' => $createdByUserId],
            );
            $count++;
        }

        return $count;
    }

    public function queueInvoice(FiscalInvoice $invoice, array $payload = []): FiscalInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new RuntimeException('Somente notas em rascunho podem entrar na fila de envio.');
        }

        $invoice->update([
            'status' => 'pending_submission',
            'queued_at' => now(config('app.timezone')),
            'rps_series' => $payload['rps_series'] ?? $invoice->rps_series,
            'rps_number' => $payload['rps_number'] ?? $invoice->rps_number ?? $this->generateRpsNumber($invoice),
        ]);

        return $invoice->refresh()->load(['unit', 'patient', 'accountReceivable', 'createdBy']);
    }

    public function submitPending(?int $unitId, int $limit = 20): int
    {
        $count = 0;

        $this->scopeInvoiceQuery(FiscalInvoice::query(), $unitId)
            ->where('status', 'pending_submission')
            ->orderBy('queued_at')
            ->limit($limit)
            ->get()
            ->each(function (FiscalInvoice $invoice) use (&$count): void {
                $invoice->update([
                    'status' => 'submitted',
                    'submitted_at' => now(config('app.timezone')),
                    'external_reference' => $invoice->external_reference ?? $this->generateProtocol($invoice),
                    'provider_response' => array_merge($invoice->provider_response ?? [], [
                        'submission_status' => 'submitted',
                        'submitted_at' => now(config('app.timezone'))->toDateTimeString(),
                    ]),
                ]);

                $count++;
            });

        return $count;
    }

    public function markAsIssued(FiscalInvoice $invoice, array $payload = []): FiscalInvoice
    {
        if (! in_array($invoice->status, ['pending_submission', 'submitted'], true)) {
            throw new RuntimeException('Somente notas enviadas ou na fila podem ser marcadas como emitidas.');
        }

        $issuedAt = filled($payload['issued_at'] ?? null)
            ? Carbon::parse((string) $payload['issued_at'], config('app.timezone'))
            : now(config('app.timezone'));

        $invoice->update([
            'status' => 'issued',
            'issue_date' => filled($payload['issue_date'] ?? null)
                ? Carbon::parse((string) $payload['issue_date'], config('app.timezone'))->toDateString()
                : $issuedAt->toDateString(),
            'issued_at' => $issuedAt,
            'municipal_invoice_number' => $payload['municipal_invoice_number'] ?? $invoice->municipal_invoice_number ?? $this->generateMunicipalNumber($invoice),
            'verification_code' => $payload['verification_code'] ?? $invoice->verification_code ?? $this->generateVerificationCode($invoice),
            'external_reference' => $payload['external_reference'] ?? $invoice->external_reference,
            'provider_response' => array_merge($invoice->provider_response ?? [], [
                'issue_status' => 'issued',
                'issued_at' => $issuedAt->toDateTimeString(),
            ]),
        ]);

        return $invoice->refresh()->load(['unit', 'patient', 'accountReceivable', 'createdBy']);
    }

    public function cancel(FiscalInvoice $invoice, array $payload = []): FiscalInvoice
    {
        if ($invoice->status === 'cancelled') {
            return $invoice->refresh()->load(['unit', 'patient', 'accountReceivable', 'createdBy']);
        }

        $invoice->update([
            'status' => 'cancelled',
            'cancelled_at' => now(config('app.timezone')),
            'last_error_message' => $payload['reason'] ?? $invoice->last_error_message,
            'provider_response' => array_merge($invoice->provider_response ?? [], [
                'cancelled_at' => now(config('app.timezone'))->toDateTimeString(),
            ]),
        ]);

        return $invoice->refresh()->load(['unit', 'patient', 'accountReceivable', 'createdBy']);
    }

    private function missingFiscalFields(?Unit $unit): array
    {
        if (! $unit) {
            return ['unidade'];
        }

        $required = [
            'razao_social' => $unit->legal_name,
            'cnpj' => $unit->document,
            'inscricao_municipal' => $unit->municipal_registration,
            'codigo_municipio_servico' => $unit->service_city_code,
            'codigo_servico_padrao' => $unit->default_service_code,
        ];

        return collect($required)
            ->filter(fn ($value) => blank($value))
            ->keys()
            ->values()
            ->all();
    }

    private function resolvePeriod(string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): array
    {
        $start = $fromDate instanceof CarbonInterface
            ? Carbon::instance($fromDate)->startOfDay()
            : Carbon::parse($fromDate ?: now(config('app.timezone'))->startOfMonth()->toDateString(), config('app.timezone'))->startOfDay();

        $end = $toDate instanceof CarbonInterface
            ? Carbon::instance($toDate)->endOfDay()
            : Carbon::parse($toDate ?: now(config('app.timezone'))->endOfMonth()->toDateString(), config('app.timezone'))->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function scopeQuery(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopeInvoiceQuery(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function generateReference(int $receivableId): string
    {
        return 'NFSE-'.now(config('app.timezone'))->format('YmdHis').'-'.$receivableId;
    }

    private function generateRpsNumber(FiscalInvoice $invoice): string
    {
        return now(config('app.timezone'))->format('Ymd').str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT);
    }

    private function generateProtocol(FiscalInvoice $invoice): string
    {
        return 'PROTO-'.now(config('app.timezone'))->format('YmdHis').'-'.$invoice->id;
    }

    private function generateMunicipalNumber(FiscalInvoice $invoice): string
    {
        return now(config('app.timezone'))->format('ymd').str_pad((string) $invoice->id, 7, '0', STR_PAD_LEFT);
    }

    private function generateVerificationCode(FiscalInvoice $invoice): string
    {
        return strtoupper(substr(sha1($invoice->reference.'|'.$invoice->id), 0, 12));
    }
}
