<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentRequestController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'procedure_id' => ['nullable', 'exists:procedures,id'],
            'name' => ['required', 'string', 'max:120'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'requested_date' => ['required', 'date'],
            'requested_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:120'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:2'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
        ]);

        $phone = $this->digits($data['phone']);
        $whatsapp = $this->digits($data['whatsapp'] ?? '');
        $cpf = $this->digits($data['cpf'] ?? '');
        $whatsappOptIn = (bool) ($data['whatsapp_opt_in'] ?? false);

        $procedure = filled($data['procedure_id'] ?? null)
            ? Procedure::query()->find($data['procedure_id'])
            : null;

        $start = Carbon::createFromFormat('Y-m-d H:i', "{$data['requested_date']} {$data['requested_time']}", config('app.timezone'));
        $end = (clone $start)->addMinutes($procedure?->default_duration_minutes ?? 60);

        DB::transaction(function () use ($data, $cpf, $phone, $whatsapp, $whatsappOptIn, $procedure, $start, $end) {
            $patient = Patient::query()
                ->when(filled($cpf), fn ($query) => $query->where('cpf', $cpf))
                ->when(blank($cpf), fn ($query) => $query->where('phone', $phone))
                ->first();

            if (! $patient) {
                $patient = Patient::query()->create([
                    'unit_id' => $data['unit_id'],
                    'name' => $data['name'],
                    'preferred_name' => $data['preferred_name'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $phone,
                    'whatsapp' => $whatsapp ?: $phone,
                    'cpf' => $cpf ?: null,
                    'zip_code' => $data['zip_code'] ?? null,
                    'street' => $data['street'] ?? null,
                    'number' => $data['number'] ?? null,
                    'complement' => $data['complement'] ?? null,
                    'district' => $data['district'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'whatsapp_opt_in' => $whatsappOptIn,
                    'whatsapp_opt_in_at' => $whatsappOptIn ? now() : null,
                    'is_active' => true,
                ]);
            } elseif ($whatsappOptIn && ! $patient->whatsapp_opt_in) {
                $patient->update([
                    'whatsapp' => $whatsapp ?: $patient->whatsapp ?: $phone,
                    'whatsapp_opt_in' => true,
                    'whatsapp_opt_in_at' => now(),
                ]);
            }

            Appointment::query()->create([
                'unit_id' => $data['unit_id'],
                'patient_id' => $patient->id,
                'procedure_id' => $procedure?->id,
                'status' => 'requested',
                'origin' => 'portal',
                'requested_at' => now(),
                'scheduled_start' => $start,
                'scheduled_end' => $end,
                'notes' => $data['notes'] ?? null,
                'meta' => [
                    'request_type' => 'public',
                ],
            ]);
        });

        $message = 'Solicitação recebida. Nossa recepção irá confirmar o horário escolhido.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    private function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', $value ?? '') ?? '';
    }
}
