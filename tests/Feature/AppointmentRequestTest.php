<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_appointment_request_creates_patient_and_requested_appointment(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $procedure = Procedure::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Avaliação inicial',
            'default_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('appointments.request'), [
            'unit_id' => $unit->id,
            'procedure_id' => $procedure->id,
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-1111',
            'whatsapp' => '(11) 99999-1111',
            'whatsapp_opt_in' => true,
            'cpf' => '123.456.789-01',
            'requested_date' => '2026-04-01',
            'requested_time' => '09:30',
            'notes' => 'Primeira avaliação.',
            'zip_code' => '01001-000',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Solicitação recebida. Nossa recepção irá confirmar o horário escolhido.');

        $this->assertDatabaseHas('patients', [
            'unit_id' => $unit->id,
            'name' => 'Maria Silva',
            'phone' => '11999991111',
            'cpf' => '12345678901',
            'whatsapp_opt_in' => true,
        ]);

        $this->assertDatabaseHas('appointments', [
            'unit_id' => $unit->id,
            'procedure_id' => $procedure->id,
            'status' => 'requested',
            'origin' => 'portal',
        ]);
    }
}
