<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCalendarFeedController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('agenda.view'), 403);

        $appointments = Appointment::query()
            ->with(['patient', 'procedure', 'professional.user', 'chair', 'unit'])
            ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->where('unit_id', $unitId))
            ->when($request->integer('professional_id'), fn ($query, $professionalId) => $query->where('professional_id', $professionalId))
            ->when($request->integer('chair_id'), fn ($query, $chairId) => $query->where('chair_id', $chairId))
            ->when($request->filled('start'), fn ($query) => $query->where('scheduled_end', '>=', $request->date('start')))
            ->when($request->filled('end'), fn ($query) => $query->where('scheduled_start', '<=', $request->date('end')))
            ->get()
            ->map(function (Appointment $appointment) {
                $status = $appointment->status;

                return [
                    'id' => $appointment->id,
                    'title' => trim(sprintf(
                        '%s%s%s',
                        $appointment->patient?->name ?? 'Paciente',
                        $appointment->procedure ? ' · '.$appointment->procedure->name : '',
                        $appointment->chair ? ' · '.$appointment->chair->name : '',
                    )),
                    'start' => optional($appointment->scheduled_start)->toIso8601String(),
                    'end' => optional($appointment->scheduled_end)->toIso8601String(),
                    'backgroundColor' => $this->statusColor($status, $appointment->professional?->agenda_color),
                    'borderColor' => $this->statusColor($status, $appointment->professional?->agenda_color),
                    'extendedProps' => [
                        'status' => $status,
                        'unit' => $appointment->unit?->name,
                        'professional' => $appointment->professional?->user?->name,
                        'chair' => $appointment->chair?->name,
                        'notes' => $appointment->notes,
                    ],
                ];
            })
            ->values();

        return response()->json($appointments);
    }

    private function statusColor(?string $status, ?string $professionalColor): string
    {
        return match ($status) {
            'requested' => '#f59e0b',
            'confirmed' => $professionalColor ?: '#2563eb',
            'checked_in' => '#0f766e',
            'in_progress' => '#7c3aed',
            'completed' => '#16a34a',
            'no_show' => '#dc2626',
            'cancelled' => '#6b7280',
            default => '#164e63',
        };
    }
}
