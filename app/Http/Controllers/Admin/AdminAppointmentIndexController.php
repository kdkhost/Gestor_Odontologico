<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Unit;
use App\Support\AdminModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminAppointmentIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $module = AdminModuleRegistry::find('agenda', $request->user());

        abort_if(blank($module), 403);

        $user = $request->user();
        $lockedUnitId = $this->lockedUnitId($user);

        $filters = $this->filters($request, $lockedUnitId);
        $baseQuery = $this->query($filters, $lockedUnitId);

        $appointments = (clone $baseQuery)
            ->orderBy('scheduled_start')
            ->paginate(15)
            ->withQueryString();

        return view('admin.appointments.index', [
            'module' => $module,
            'filters' => $filters,
            'appointments' => $appointments,
            'statusOptions' => $this->statusOptions(),
            'statusPalette' => $this->statusPalette(),
            'summary' => $this->summary($baseQuery),
            'units' => $this->units($lockedUnitId),
            'professionals' => $this->professionals($filters['unit_id']),
            'lockedUnitId' => $lockedUnitId,
        ]);
    }

    private function query(array $filters, ?int $lockedUnitId): Builder
    {
        return Appointment::query()
            ->with(['patient', 'procedure', 'professional.user', 'unit', 'chair'])
            ->when($lockedUnitId, fn (Builder $query) => $query->where('unit_id', $lockedUnitId))
            ->when(! $lockedUnitId && $filters['unit_id'], fn (Builder $query, int $unitId) => $query->where('unit_id', $unitId))
            ->when($filters['professional_id'], fn (Builder $query, int $professionalId) => $query->where('professional_id', $professionalId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->whereDate('scheduled_start', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->whereDate('scheduled_start', '<=', $date))
            ->when($filters['q'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereHas('patient', fn (Builder $patient) => $patient->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('procedure', fn (Builder $procedure) => $procedure->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('unit', fn (Builder $unit) => $unit->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('chair', fn (Builder $chair) => $chair->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('professional.user', fn (Builder $professional) => $professional->where('name', 'like', "%{$search}%"));
                });
            });
    }

    private function filters(Request $request, ?int $lockedUnitId): array
    {
        $today = now(config('app.timezone'))->toDateString();
        $dateFrom = (string) $request->string('date_from', $today);
        $dateTo = (string) $request->string('date_to', $today);

        if ($dateTo < $dateFrom) {
            $dateTo = $dateFrom;
        }

        return [
            'q' => trim((string) $request->string('q')),
            'status' => (string) $request->string('status'),
            'unit_id' => $lockedUnitId ?: $request->integer('unit_id'),
            'professional_id' => $request->integer('professional_id'),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function summary(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'requested' => (clone $query)->where('status', 'requested')->count(),
            'active' => (clone $query)->whereIn('status', ['confirmed', 'checked_in', 'in_progress'])->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'issues' => (clone $query)->whereIn('status', ['no_show', 'cancelled'])->count(),
        ];
    }

    private function units(?int $lockedUnitId): array
    {
        return Unit::query()
            ->when($lockedUnitId, fn (Builder $query) => $query->whereKey($lockedUnitId))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function professionals(?int $unitId): array
    {
        return Professional::query()
            ->with('user')
            ->when($unitId, fn (Builder $query, int $resolvedUnitId) => $query->where('unit_id', $resolvedUnitId))
            ->get()
            ->mapWithKeys(fn (Professional $professional) => [$professional->id => $professional->user?->name ?? "Profissional #{$professional->id}"])
            ->all();
    }

    private function statusOptions(): array
    {
        return collect(config('clinic.appointment_statuses', []))
            ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
            ->all();
    }

    private function statusPalette(): array
    {
        return [
            'requested' => 'warning',
            'confirmed' => 'primary',
            'checked_in' => 'info',
            'in_progress' => 'purple',
            'completed' => 'success',
            'no_show' => 'danger',
            'cancelled' => 'secondary',
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'requested' => 'Solicitado',
            'confirmed' => 'Confirmado',
            'checked_in' => 'Check-in',
            'in_progress' => 'Em atendimento',
            'completed' => 'Concluido',
            'no_show' => 'Falta',
            'cancelled' => 'Cancelado',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function lockedUnitId(mixed $user): ?int
    {
        if (! $user) {
            return null;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return null;
        }

        return $user->unit_id;
    }
}
