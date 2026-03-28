<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Unit;
use App\Support\AdminModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminPatientIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $module = AdminModuleRegistry::find('pacientes', $request->user());

        abort_if(blank($module), 403);

        $user = $request->user();
        $lockedUnitId = $this->lockedUnitId($user);
        $filters = $this->filters($request, $lockedUnitId);
        $baseQuery = $this->query($filters, $lockedUnitId);
        $patients = (clone $baseQuery)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $patients->getCollection()->transform(function (Patient $patient): Patient {
            $attention = $this->attention($patient);

            $patient->setAttribute('attention_level', $attention['level']);
            $patient->setAttribute('attention_label', $attention['label']);
            $patient->setAttribute('attention_color', $attention['color']);
            $patient->setAttribute('needs_reactivation', $attention['needs_reactivation']);

            return $patient;
        });

        return view('admin.patients.index', [
            'module' => $module,
            'filters' => $filters,
            'patients' => $patients,
            'units' => $this->units($lockedUnitId),
            'summary' => $this->summary($baseQuery),
            'lockedUnitId' => $lockedUnitId,
        ]);
    }

    private function query(array $filters, ?int $lockedUnitId): Builder
    {
        $now = now(config('app.timezone'));

        return Patient::query()
            ->with(['unit', 'latestAppointment'])
            ->withCount([
                'accountsReceivable as overdue_receivables_count' => fn (Builder $query) => $query
                    ->whereIn('status', ['open', 'partial', 'overdue'])
                    ->whereDate('due_date', '<', $now->toDateString()),
                'appointments as upcoming_appointments_count' => fn (Builder $query) => $query
                    ->where('scheduled_start', '>=', $now)
                    ->whereNotIn('status', ['cancelled', 'no_show']),
                'appointments as no_show_recent_count' => fn (Builder $query) => $query
                    ->where('status', 'no_show')
                    ->where('scheduled_start', '>=', $now->copy()->subDays(180)),
            ])
            ->when($lockedUnitId, fn (Builder $query) => $query->where('unit_id', $lockedUnitId))
            ->when(! $lockedUnitId && $filters['unit_id'], fn (Builder $query, int $unitId) => $query->where('unit_id', $unitId))
            ->when($filters['active'] !== 'all', fn (Builder $query) => $query->where('is_active', $filters['active'] === 'active'))
            ->when($filters['whatsapp_opt_in'] !== 'all', fn (Builder $query) => $query->where('whatsapp_opt_in', $filters['whatsapp_opt_in'] === 'yes'))
            ->when($filters['q'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('preferred_name', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
    }

    private function filters(Request $request, ?int $lockedUnitId): array
    {
        return [
            'q' => trim((string) $request->string('q')),
            'unit_id' => $lockedUnitId ?: $request->integer('unit_id'),
            'active' => (string) $request->string('active', 'active'),
            'whatsapp_opt_in' => (string) $request->string('whatsapp_opt_in', 'all'),
        ];
    }

    private function summary(Builder $query): array
    {
        $now = now(config('app.timezone'));

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'whatsapp_opt_in' => (clone $query)->where('whatsapp_opt_in', true)->count(),
            'overdue' => (clone $query)
                ->whereHas('accountsReceivable', fn (Builder $receivables) => $receivables
                    ->whereIn('status', ['open', 'partial', 'overdue'])
                    ->whereDate('due_date', '<', $now->toDateString()))
                ->count(),
            'reactivation' => (clone $query)
                ->whereNotNull('last_visit_at')
                ->where('last_visit_at', '<=', $now->copy()->subDays(120))
                ->whereDoesntHave('appointments', fn (Builder $appointments) => $appointments
                    ->where('scheduled_start', '>=', $now)
                    ->whereNotIn('status', ['cancelled', 'no_show']))
                ->count(),
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

    private function attention(Patient $patient): array
    {
        $needsReactivation = filled($patient->last_visit_at)
            && $patient->last_visit_at->lte(now(config('app.timezone'))->copy()->subDays(120))
            && (int) $patient->upcoming_appointments_count === 0;

        return match (true) {
            (int) $patient->overdue_receivables_count > 0 && (int) $patient->no_show_recent_count >= 2 => [
                'level' => 'critico',
                'label' => 'Critico',
                'color' => 'danger',
                'needs_reactivation' => $needsReactivation,
            ],
            (int) $patient->overdue_receivables_count > 0 || (int) $patient->no_show_recent_count >= 2 || $needsReactivation => [
                'level' => 'alerta',
                'label' => 'Alerta',
                'color' => 'warning',
                'needs_reactivation' => $needsReactivation,
            ],
            default => [
                'level' => 'estavel',
                'label' => 'Estavel',
                'color' => 'success',
                'needs_reactivation' => false,
            ],
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
