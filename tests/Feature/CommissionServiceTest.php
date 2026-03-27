<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_receivable_generates_or_updates_commission_entry(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Dra. Paula',
            'email' => 'paula@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'commission_percentage' => 12.5,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Carlos Lima',
            'is_active' => true,
        ]);

        $appointment = Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'status' => 'completed',
            'origin' => 'admin',
            'requested_at' => now(),
            'scheduled_start' => now()->subDay(),
            'scheduled_end' => now()->subDay()->addHour(),
            'finished_at' => now()->subDay()->addHour(),
        ]);

        $account = AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'reference' => 'REC-COM-001',
            'description' => 'Tratamento',
            'status' => 'paid',
            'total_amount' => 800,
            'net_amount' => 800,
            'paid_at' => now(),
        ]);

        $entry = app(CommissionService::class)->syncForReceivable($account->refresh());

        $this->assertNotNull($entry);
        $this->assertDatabaseHas('commission_entries', [
            'account_receivable_id' => $account->id,
            'professional_id' => $professional->id,
            'base_amount' => 800,
            'percentage' => 12.5,
            'amount' => 100,
            'status' => 'pending',
        ]);

        $account->update([
            'net_amount' => 960,
            'paid_at' => now()->addMinute(),
        ]);

        $this->assertDatabaseHas('commission_entries', [
            'account_receivable_id' => $account->id,
            'amount' => 120,
        ]);
        $this->assertDatabaseCount('commission_entries', 1);
    }
}
