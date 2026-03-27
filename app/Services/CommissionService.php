<?php

namespace App\Services;

use App\Models\AccountReceivable;
use App\Models\CommissionEntry;
use App\Models\Professional;
use Illuminate\Support\Carbon;

class CommissionService
{
    public function syncForReceivable(AccountReceivable $account): ?CommissionEntry
    {
        $account->loadMissing([
            'appointment.professional',
            'treatmentPlan.professional',
        ]);

        $professional = $this->resolveProfessional($account);
        $entry = CommissionEntry::query()
            ->where('account_receivable_id', $account->id)
            ->first();

        if (! $professional || (float) $professional->commission_percentage <= 0) {
            if ($entry && $entry->status !== 'paid') {
                $entry->delete();
            }

            return null;
        }

        if ($account->status !== 'paid') {
            if ($entry && $entry->status !== 'paid') {
                $entry->update([
                    'status' => 'cancelled',
                    'calculated_at' => $entry->calculated_at ?? now(),
                    'paid_at' => null,
                ]);
            }

            return $entry;
        }

        $percentage = round((float) $professional->commission_percentage, 2);
        $baseAmount = round((float) $account->net_amount, 2);
        $amount = round($baseAmount * ($percentage / 100), 2);

        $entry ??= new CommissionEntry;

        $entry->fill([
            'unit_id' => $account->unit_id,
            'professional_id' => $professional->id,
            'account_receivable_id' => $account->id,
            'appointment_id' => $account->appointment_id,
            'base_amount' => $baseAmount,
            'percentage' => $percentage,
            'amount' => $amount,
            'status' => $entry->exists && in_array($entry->status, ['batched', 'paid'], true)
                ? $entry->status
                : 'pending',
            'calculated_at' => $entry->calculated_at ?? $account->paid_at ?? now(),
            'paid_at' => $entry->exists && $entry->status === 'paid' ? $entry->paid_at : null,
        ]);

        $entry->save();

        return $entry->refresh();
    }

    public function syncPaidSince(?Carbon $from = null): int
    {
        $query = AccountReceivable::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at');

        if ($from) {
            $query->where('paid_at', '>=', $from);
        }

        $count = 0;

        $query->with(['appointment.professional', 'treatmentPlan.professional'])
            ->chunkById(200, function ($accounts) use (&$count): void {
                foreach ($accounts as $account) {
                    if ($this->syncForReceivable($account)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function resolveProfessional(AccountReceivable $account): ?Professional
    {
        return $account->appointment?->professional
            ?? $account->treatmentPlan?->professional;
    }
}
