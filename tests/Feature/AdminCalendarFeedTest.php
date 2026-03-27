<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminCalendarFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_feed_returns_filtered_appointments_for_authorized_user(): void
    {
        $this->markApplicationAsInstalled();

        Permission::query()->create([
            'name' => 'agenda.view',
            'guard_name' => 'web',
        ]);

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Recepção',
            'email' => 'recepcao@example.com',
            'user_type' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => true,
            'password' => 'secret',
        ]);
        $user->givePermissionTo('agenda.view');

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'João da Agenda',
            'phone' => '11999990000',
            'is_active' => true,
        ]);

        $professionalUser = User::query()->create([
            'name' => 'Dra. Helena',
            'email' => 'helena@example.com',
            'user_type' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => true,
            'password' => 'secret',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $professionalUser->id,
            'unit_id' => $unit->id,
            'specialty' => 'Ortodontia',
            'agenda_color' => '#2563eb',
        ]);

        $chair = Chair::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Sala 01',
            'is_active' => true,
        ]);

        $procedure = Procedure::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Limpeza',
            'default_duration_minutes' => 30,
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'chair_id' => $chair->id,
            'procedure_id' => $procedure->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => '2026-04-02 10:00:00',
            'scheduled_end' => '2026-04-02 10:30:00',
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.calendar.feed', [
                'unit_id' => $unit->id,
                'start' => '2026-04-01T00:00:00-03:00',
                'end' => '2026-04-03T00:00:00-03:00',
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.professional', 'Dra. Helena')
            ->assertJsonPath('0.extendedProps.chair', 'Sala 01');
    }
}
