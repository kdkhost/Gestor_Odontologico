<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_overlapping_appointments_for_the_same_professional(): void
    {
        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $professionalUser = User::query()->create([
            'name' => 'Dra. Paula',
            'email' => 'paula@example.com',
            'unit_id' => $unit->id,
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $professionalUser->id,
            'unit_id' => $unit->id,
            'license_type' => 'CRO',
        ]);

        $patientA = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente A',
            'is_active' => true,
        ]);

        $patientB = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente B',
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patientA->id,
            'professional_id' => $professional->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->setHour(9)->setMinute(0),
            'scheduled_end' => now()->setHour(10)->setMinute(0),
        ]);

        $this->expectException(ValidationException::class);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patientB->id,
            'professional_id' => $professional->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->setHour(9)->setMinute(30),
            'scheduled_end' => now()->setHour(10)->setMinute(30),
        ]);
    }

    public function test_public_request_returns_validation_error_when_patient_already_has_conflicting_time(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Maria Silva',
            'phone' => '11999991111',
            'cpf' => '12345678901',
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'requested',
            'origin' => 'portal',
            'scheduled_start' => now()->setDate(2026, 4, 1)->setTime(9, 30),
            'scheduled_end' => now()->setDate(2026, 4, 1)->setTime(10, 30),
        ]);

        $this->postJson(route('appointments.request'), [
            'unit_id' => $unit->id,
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-1111',
            'cpf' => '123.456.789-01',
            'requested_date' => '2026-04-01',
            'requested_time' => '10:00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id', 'scheduled_start']);
    }

    public function test_it_blocks_overlapping_appointments_for_the_same_chair(): void
    {
        $unit = Unit::query()->create([
            'name' => 'Filial',
            'slug' => 'filial',
            'is_active' => true,
        ]);

        $chair = Chair::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Sala 1',
            'is_active' => true,
        ]);

        $patientA = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente C',
            'is_active' => true,
        ]);

        $patientB = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente D',
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patientA->id,
            'chair_id' => $chair->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->setHour(14)->setMinute(0),
            'scheduled_end' => now()->setHour(15)->setMinute(0),
        ]);

        $this->expectException(ValidationException::class);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patientB->id,
            'chair_id' => $chair->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->setHour(14)->setMinute(15),
            'scheduled_end' => now()->setHour(15)->setMinute(15),
        ]);
    }
}
