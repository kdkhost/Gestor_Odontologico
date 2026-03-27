<?php

namespace App\Services;

use App\Models\CommissionEntry;
use App\Models\CommissionSettlement;
use App\Models\Professional;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommissionSettlementService
{
    public function pendingCandidates(?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate): Collection
    {
        [$start, $end] = $this->resolvePeriod($fromDate, $toDate);

        return $this->scopeQuery(
            CommissionEntry::query()->with(['professional.user', 'professional.unit']),
            $unitId,
        )
            ->where('status', 'pending')
            ->whereNull('commission_settlement_id')
            ->whereNotNull('calculated_at')
            ->whereBetween('calculated_at', [$start, $end])
            ->get()
            ->groupBy('professional_id')
            ->map(function (Collection $entries): array {
                /** @var CommissionEntry $first */
                $first = $entries->first();

                return [
                    'professional_id' => $first->professional_id,
                    'professional_name' => $first->professional?->user?->name ?? 'Profissional',
                    'unit_id' => $first->unit_id,
                    'unit_name' => $first->professional?->unit?->name ?? 'Sem unidade',
                    'commission_count' => $entries->count(),
                    'gross_amount' => round((float) $entries->sum('amount'), 2),
                    'first_calculated_at' => $entries->min('calculated_at'),
                    'last_calculated_at' => $entries->max('calculated_at'),
                ];
            })
            ->sortByDesc('gross_amount')
            ->values();
    }

    public function recentSettlements(?int $unitId, int $limit = 15): Collection
    {
        return $this->scopeSettlementQuery(
            CommissionSettlement::query()->with(['professional.user', 'unit', 'paidBy', 'reconciledBy']),
            $unitId,
        )
            ->latest('closed_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function createSettlement(int $professionalId, ?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate, ?int $createdByUserId = null, ?string $notes = null): CommissionSettlement
    {
        [$start, $end] = $this->resolvePeriod($fromDate, $toDate);

        return DB::transaction(function () use ($professionalId, $unitId, $start, $end, $createdByUserId, $notes) {
            $entries = $this->pendingEntriesQuery($professionalId, $unitId, $start, $end)
                ->lockForUpdate()
                ->get();

            if ($entries->isEmpty()) {
                throw new RuntimeException('Nenhuma comissão pendente encontrada para fechamento.');
            }

            $professional = Professional::query()->findOrFail($professionalId);
            $unitId = $unitId ?: $entries->first()->unit_id;

            $settlement = CommissionSettlement::query()->create([
                'unit_id' => $unitId,
                'professional_id' => $professionalId,
                'created_by_user_id' => $createdByUserId,
                'reference' => $this->generateReference($professionalId),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'commission_count' => $entries->count(),
                'gross_amount' => round((float) $entries->sum('amount'), 2),
                'status' => 'closed',
                'closed_at' => now(config('app.timezone')),
                'notes' => $notes ?: 'Fechamento gerado pelo administrativo.',
            ]);

            CommissionEntry::query()
                ->whereIn('id', $entries->pluck('id'))
                ->update([
                    'commission_settlement_id' => $settlement->id,
                    'status' => 'batched',
                    'updated_at' => now(),
                ]);

            return $settlement->load(['professional.user', 'unit']);
        });
    }

    public function markAsPaid(CommissionSettlement $settlement): CommissionSettlement
    {
        return $this->registerPayment($settlement, []);
    }

    public function registerPayment(CommissionSettlement $settlement, array $payload = []): CommissionSettlement
    {
        if ($settlement->status === 'paid') {
            return $settlement->fresh(['professional.user', 'unit', 'paidBy', 'reconciledBy']);
        }

        if ($settlement->status === 'cancelled') {
            throw new RuntimeException('Não é possível registrar pagamento em um repasse cancelado.');
        }

        return DB::transaction(function () use ($settlement, $payload) {
            $paidAt = filled($payload['paid_at'] ?? null)
                ? Carbon::parse((string) $payload['paid_at'], config('app.timezone'))
                : now(config('app.timezone'));

            $settlement->update([
                'status' => 'paid',
                'paid_at' => $paidAt,
                'payment_method' => $payload['payment_method'] ?? $settlement->payment_method ?? 'manual',
                'payment_reference' => $payload['payment_reference'] ?? $settlement->payment_reference,
                'proof_path' => $payload['proof_path'] ?? $settlement->proof_path,
                'paid_by_user_id' => $payload['paid_by_user_id'] ?? $settlement->paid_by_user_id,
                'notes' => $payload['notes'] ?? $settlement->notes,
            ]);

            CommissionEntry::query()
                ->where('commission_settlement_id', $settlement->id)
                ->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'updated_at' => now(),
                ]);

            return $settlement->refresh()->load(['professional.user', 'unit', 'paidBy', 'reconciledBy']);
        });
    }

    public function markAsReconciled(CommissionSettlement $settlement, array $payload = []): CommissionSettlement
    {
        if ($settlement->status !== 'paid') {
            throw new RuntimeException('Somente repasses pagos podem ser conciliados.');
        }

        return DB::transaction(function () use ($settlement, $payload) {
            $reconciledAt = filled($payload['reconciled_at'] ?? null)
                ? Carbon::parse((string) $payload['reconciled_at'], config('app.timezone'))
                : now(config('app.timezone'));

            $settlement->update([
                'bank_statement_reference' => $payload['bank_statement_reference'] ?? $settlement->bank_statement_reference,
                'reconciled_at' => $reconciledAt,
                'reconciled_by_user_id' => $payload['reconciled_by_user_id'] ?? $settlement->reconciled_by_user_id,
                'reconciliation_notes' => $payload['reconciliation_notes'] ?? $settlement->reconciliation_notes,
            ]);

            return $settlement->refresh()->load(['professional.user', 'unit', 'paidBy', 'reconciledBy']);
        });
    }

    public function cancel(CommissionSettlement $settlement): CommissionSettlement
    {
        if ($settlement->status === 'paid') {
            throw new RuntimeException('Não é possível cancelar um repasse já pago.');
        }

        return DB::transaction(function () use ($settlement) {
            CommissionEntry::query()
                ->where('commission_settlement_id', $settlement->id)
                ->update([
                    'commission_settlement_id' => null,
                    'status' => 'pending',
                    'paid_at' => null,
                    'updated_at' => now(),
                ]);

            $settlement->update([
                'status' => 'cancelled',
            ]);

            return $settlement->refresh()->load(['professional.user', 'unit', 'paidBy', 'reconciledBy']);
        });
    }

    public function closeAllPending(?int $unitId, string|CarbonInterface|null $fromDate, string|CarbonInterface|null $toDate, ?int $createdByUserId = null): int
    {
        $count = 0;

        $this->pendingCandidates($unitId, $fromDate, $toDate)
            ->each(function (array $candidate) use ($fromDate, $toDate, $createdByUserId, &$count): void {
                $this->createSettlement(
                    professionalId: (int) $candidate['professional_id'],
                    unitId: $candidate['unit_id'] ? (int) $candidate['unit_id'] : null,
                    fromDate: $fromDate,
                    toDate: $toDate,
                    createdByUserId: $createdByUserId,
                );

                $count++;
            });

        return $count;
    }

    private function pendingEntriesQuery(int $professionalId, ?int $unitId, Carbon $start, Carbon $end): Builder
    {
        return $this->scopeQuery(
            CommissionEntry::query(),
            $unitId,
        )
            ->where('professional_id', $professionalId)
            ->where('status', 'pending')
            ->whereNull('commission_settlement_id')
            ->whereNotNull('calculated_at')
            ->whereBetween('calculated_at', [$start, $end]);
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

    private function scopeSettlementQuery(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function generateReference(int $professionalId): string
    {
        return 'REP-'.now(config('app.timezone'))->format('YmdHis').'-'.$professionalId;
    }
}
