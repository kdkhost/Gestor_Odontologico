<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AppointmentConflictService
{
    public function validate(Appointment $appointment): void
    {
        if (! $appointment->unit_id || ! $appointment->scheduled_start || ! $appointment->scheduled_end) {
            return;
        }

        if ($appointment->scheduled_end->lessThanOrEqualTo($appointment->scheduled_start)) {
            throw ValidationException::withMessages([
                'scheduled_end' => 'O horário final precisa ser maior que o horário inicial.',
            ]);
        }

        $conflicts = $this->findConflicts($appointment);

        if ($conflicts->isEmpty()) {
            return;
        }

        $messages = [];

        if ($appointment->professional_id && $conflicts->contains(fn (Appointment $item) => (int) $item->professional_id === (int) $appointment->professional_id)) {
            $messages['professional_id'] = 'O profissional selecionado já possui outro atendimento neste horário.';
        }

        if ($appointment->chair_id && $conflicts->contains(fn (Appointment $item) => (int) $item->chair_id === (int) $appointment->chair_id)) {
            $messages['chair_id'] = 'A cadeira ou sala selecionada já está ocupada neste horário.';
        }

        if ($appointment->patient_id && $conflicts->contains(fn (Appointment $item) => (int) $item->patient_id === (int) $appointment->patient_id)) {
            $messages['patient_id'] = 'O paciente já possui outro agendamento conflitante neste horário.';
        }

        $messages['scheduled_start'] = $this->buildConflictSummary($conflicts);

        throw ValidationException::withMessages($messages);
    }

    public function findConflicts(Appointment $appointment): Collection
    {
        if (! $appointment->professional_id && ! $appointment->chair_id && ! $appointment->patient_id) {
            return collect();
        }

        return Appointment::query()
            ->with(['patient', 'professional.user', 'chair'])
            ->where('unit_id', $appointment->unit_id)
            ->when($appointment->exists, fn ($query) => $query->whereKeyNot($appointment->getKey()))
            ->whereNotIn('status', ['cancelled'])
            ->where('scheduled_start', '<', $appointment->scheduled_end)
            ->where('scheduled_end', '>', $appointment->scheduled_start)
            ->where(function ($query) use ($appointment) {
                if ($appointment->professional_id) {
                    $query->orWhere('professional_id', $appointment->professional_id);
                }

                if ($appointment->chair_id) {
                    $query->orWhere('chair_id', $appointment->chair_id);
                }

                if ($appointment->patient_id) {
                    $query->orWhere('patient_id', $appointment->patient_id);
                }
            })
            ->orderBy('scheduled_start')
            ->get();
    }

    private function buildConflictSummary(Collection $conflicts): string
    {
        $details = $conflicts
            ->take(3)
            ->map(function (Appointment $appointment) {
                $responsible = $appointment->professional?->user?->name
                    ?? $appointment->chair?->name
                    ?? $appointment->patient?->name
                    ?? 'agendamento existente';

                return sprintf(
                    '%s em %s',
                    $responsible,
                    optional($appointment->scheduled_start)->format('d/m/Y H:i') ?? '-',
                );
            })
            ->implode('; ');

        return 'Conflito de agenda detectado. Ajuste o horário ou os recursos do atendimento. '.trim($details);
    }
}
