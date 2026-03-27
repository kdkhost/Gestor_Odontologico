<?php

namespace App\Services;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OperationsInsightService
{
    public function snapshot(?int $unitId = null, int $days = 15, int $limit = 5): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);

        return [
            'scope' => [
                'unit_id' => $unitId,
                'label' => $this->resolveScopeLabel($unitId),
            ],
            'stats' => $this->stats($unitId),
            'trends' => [
                'labels' => $this->dateLabels($days)->map(fn (Carbon $date) => $date->format('d/m'))->all(),
                'appointments' => $this->appointmentTrend($days, $unitId),
                'revenue' => $this->revenueTrend($days, $unitId),
            ],
            'alerts' => $this->alerts($unitId, $limit),
        ];
    }

    public function stats(?int $unitId = null): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);
        $today = now(config('app.timezone'))->startOfDay();

        $todayAppointments = $this->scopeQuery(Appointment::query(), $unitId)
            ->whereBetween('scheduled_start', [$today, $today->copy()->endOfDay()])
            ->count();

        $todayPendingConfirmation = $this->scopeQuery(Appointment::query(), $unitId)
            ->whereBetween('scheduled_start', [$today, $today->copy()->endOfDay()])
            ->where('status', 'requested')
            ->count();

        $overdueTotal = (float) $this->scopeQuery(AccountReceivable::query(), $unitId)
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->sum('net_amount');

        $criticalStockCount = $this->scopeQuery(InventoryItem::query(), $unitId)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        $expiringBatchCount = $this->scopeQuery(InventoryBatch::query(), $unitId)
            ->whereDate('expires_at', '>=', $today->toDateString())
            ->whereDate('expires_at', '<=', $today->copy()->addDays(30)->toDateString())
            ->where('quantity_available', '>', 0)
            ->count();

        return [
            'today_appointments' => $todayAppointments,
            'today_pending_confirmation' => $todayPendingConfirmation,
            'confirmation_rate' => $this->confirmationRate($unitId),
            'overdue_total' => $overdueTotal,
            'critical_stock_count' => $criticalStockCount,
            'expiring_batch_count' => $expiringBatchCount,
            'no_show_rate' => $this->noShowRate($unitId),
        ];
    }

    public function alerts(?int $unitId = null, int $limit = 5): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);
        $now = now(config('app.timezone'));

        return [
            'appointments_needing_confirmation' => $this->scopeQuery(
                Appointment::query()->with(['patient', 'professional.user', 'unit']),
                $unitId,
            )
                ->where('status', 'requested')
                ->whereBetween('scheduled_start', [$now, $now->copy()->addDay()])
                ->orderBy('scheduled_start')
                ->limit($limit)
                ->get(),
            'overdue_receivables' => $this->scopeQuery(
                AccountReceivable::query()->with(['patient', 'unit']),
                $unitId,
            )
                ->whereIn('status', ['open', 'partial', 'overdue'])
                ->whereDate('due_date', '<', $now->toDateString())
                ->orderBy('due_date')
                ->limit($limit)
                ->get(),
            'low_stock_items' => $this->scopeQuery(
                InventoryItem::query()->with(['unit', 'category']),
                $unitId,
            )
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->orderByRaw('current_stock asc')
                ->limit($limit)
                ->get(),
            'expiring_batches' => $this->scopeQuery(
                InventoryBatch::query()->with(['item', 'unit']),
                $unitId,
            )
                ->whereDate('expires_at', '>=', $now->toDateString())
                ->whereDate('expires_at', '<=', $now->copy()->addDays(30)->toDateString())
                ->where('quantity_available', '>', 0)
                ->orderBy('expires_at')
                ->limit($limit)
                ->get(),
            'repeat_no_show_patients' => $this->scopeQuery(
                Appointment::query()->with(['patient', 'unit']),
                $unitId,
            )
                ->select('patient_id', 'unit_id')
                ->selectRaw('count(*) as misses')
                ->selectRaw('max(scheduled_start) as last_no_show_at')
                ->where('status', 'no_show')
                ->where('scheduled_start', '>=', $now->copy()->subDays(120))
                ->groupBy('patient_id', 'unit_id')
                ->havingRaw('count(*) >= 2')
                ->orderByDesc('misses')
                ->limit($limit)
                ->get(),
            'reactivation_candidates' => $this->scopeQuery(
                Patient::query()->with(['unit', 'latestAppointment']),
                $unitId,
            )
                ->whereNotNull('last_visit_at')
                ->where('last_visit_at', '<=', $now->copy()->subDays(90))
                ->whereDoesntHave('appointments', function (Builder $query) use ($now) {
                    $query
                        ->where('scheduled_start', '>=', $now)
                        ->whereNotIn('status', ['cancelled', 'no_show']);
                })
                ->orderBy('last_visit_at')
                ->limit($limit)
                ->get(),
        ];
    }

    public function appointmentTrend(int $days = 15, ?int $unitId = null): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);
        $dates = $this->dateLabels($days);
        $start = $dates->first()?->copy()->startOfDay() ?? now(config('app.timezone'))->subDays($days - 1)->startOfDay();
        $end = $dates->last()?->copy()->endOfDay() ?? now(config('app.timezone'))->endOfDay();

        $series = $this->scopeQuery(Appointment::query(), $unitId)
            ->whereBetween('scheduled_start', [$start, $end])
            ->whereIn('status', ['confirmed', 'checked_in', 'in_progress', 'completed'])
            ->selectRaw('date(scheduled_start) as day')
            ->selectRaw('count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $dates
            ->map(fn (Carbon $date) => (int) ($series[$date->toDateString()] ?? 0))
            ->all();
    }

    public function revenueTrend(int $days = 15, ?int $unitId = null): array
    {
        $unitId = $this->resolveScopeUnitId($unitId);
        $dates = $this->dateLabels($days);
        $start = $dates->first()?->copy()->startOfDay() ?? now(config('app.timezone'))->subDays($days - 1)->startOfDay();
        $end = $dates->last()?->copy()->endOfDay() ?? now(config('app.timezone'))->endOfDay();

        $series = $this->scopeQuery(AccountReceivable::query(), $unitId)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('date(paid_at) as day')
            ->selectRaw('sum(net_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $dates
            ->map(fn (Carbon $date) => round((float) ($series[$date->toDateString()] ?? 0), 2))
            ->all();
    }

    private function confirmationRate(?int $unitId = null): float
    {
        $start = now(config('app.timezone'))->subDays(7)->startOfDay();
        $query = $this->scopeQuery(Appointment::query(), $unitId)
            ->whereBetween('scheduled_start', [$start, now(config('app.timezone'))->endOfDay()]);

        $total = (clone $query)->count();

        if ($total === 0) {
            return 0.0;
        }

        $confirmed = (clone $query)
            ->whereIn('status', ['confirmed', 'checked_in', 'in_progress', 'completed'])
            ->count();

        return round(($confirmed / $total) * 100, 1);
    }

    private function noShowRate(?int $unitId = null): float
    {
        $start = now(config('app.timezone'))->subDays(30)->startOfDay();
        $query = $this->scopeQuery(Appointment::query(), $unitId)
            ->whereBetween('scheduled_start', [$start, now(config('app.timezone'))->endOfDay()]);

        $total = (clone $query)->count();

        if ($total === 0) {
            return 0.0;
        }

        $misses = (clone $query)
            ->where('status', 'no_show')
            ->count();

        return round(($misses / $total) * 100, 1);
    }

    private function dateLabels(int $days): Collection
    {
        $days = max(1, $days);
        $start = now(config('app.timezone'))->subDays($days - 1)->startOfDay();

        return collect(range(0, $days - 1))
            ->map(fn (int $offset) => $start->copy()->addDays($offset));
    }

    private function resolveScopeUnitId(?int $unitId = null): ?int
    {
        if ($unitId !== null) {
            return $unitId;
        }

        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return null;
        }

        return $user->unit_id;
    }

    private function resolveScopeLabel(?int $unitId): string
    {
        if ($unitId === null) {
            return 'Todas as unidades';
        }

        return Unit::query()->find($unitId)?->name ?? "Unidade #{$unitId}";
    }

    private function scopeQuery(Builder $query, ?int $unitId, string $column = 'unit_id'): Builder
    {
        if ($unitId !== null) {
            $query->where($column, $unitId);
        }

        return $query;
    }
}
