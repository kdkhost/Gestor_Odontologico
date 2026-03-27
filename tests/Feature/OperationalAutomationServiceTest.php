<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\PaymentInstallment;
use App\Models\Unit;
use App\Services\OperationalAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_automation_preview_maps_all_three_flows(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        NotificationTemplate::query()->create([
            'name' => 'Lembrete Consulta',
            'channel' => 'whatsapp',
            'provider' => 'evolution',
            'trigger_event' => 'appointment.reminder',
            'subject' => 'Consulta',
            'message' => 'Oi {{paciente_nome}}',
            'cooldown_seconds' => 120,
            'hourly_limit_per_recipient' => 2,
            'requires_opt_in' => true,
            'requires_official_window' => true,
            'is_active' => true,
        ]);

        NotificationTemplate::query()->create([
            'name' => 'Lembrete Parcela',
            'channel' => 'whatsapp',
            'provider' => 'evolution',
            'trigger_event' => 'financial.installment_due',
            'subject' => 'Parcela',
            'message' => 'Oi {{paciente_nome}}',
            'cooldown_seconds' => 120,
            'hourly_limit_per_recipient' => 2,
            'requires_opt_in' => true,
            'requires_official_window' => true,
            'is_active' => true,
        ]);

        NotificationTemplate::query()->create([
            'name' => 'Reativação',
            'channel' => 'whatsapp',
            'provider' => 'evolution',
            'trigger_event' => 'patient.reactivation',
            'subject' => 'Retorno',
            'message' => 'Oi {{paciente_nome}}',
            'cooldown_seconds' => 120,
            'hourly_limit_per_recipient' => 1,
            'requires_opt_in' => true,
            'requires_official_window' => true,
            'is_active' => true,
        ]);

        $reminderPatient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente Consulta',
            'phone' => '11999990001',
            'whatsapp' => '11999990001',
            'whatsapp_opt_in' => true,
            'whatsapp_opt_in_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $financialPatient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente Financeiro',
            'phone' => '11999990002',
            'whatsapp' => '11999990002',
            'whatsapp_opt_in' => true,
            'whatsapp_opt_in_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente Reativacao',
            'phone' => '11999990003',
            'whatsapp' => '11999990003',
            'whatsapp_opt_in' => true,
            'whatsapp_opt_in_at' => now()->subDays(20),
            'last_visit_at' => now()->subDays(120),
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $reminderPatient->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'requested_at' => now(),
            'scheduled_start' => now()->addHours(12),
            'scheduled_end' => now()->addHours(13),
        ]);

        $receivable = AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $financialPatient->id,
            'reference' => 'REC-AUTO-001',
            'description' => 'Plano ortodontico',
            'status' => 'open',
            'total_amount' => 250,
            'net_amount' => 250,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        PaymentInstallment::query()->create([
            'account_receivable_id' => $receivable->id,
            'installment_number' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'amount' => 250,
            'balance' => 250,
            'status' => 'open',
            'meta' => [],
        ]);

        $results = app(OperationalAutomationService::class)->runAll(true);

        $this->assertSame('preview', $results['appointment_reminder']['status']);
        $this->assertSame(1, $results['appointment_reminder']['matched_count']);
        $this->assertSame(0, $results['appointment_reminder']['sent_count']);

        $this->assertSame('preview', $results['financial_due']['status']);
        $this->assertSame(1, $results['financial_due']['matched_count']);
        $this->assertSame(0, $results['financial_due']['sent_count']);

        $this->assertSame('preview', $results['patient_reactivation']['status']);
        $this->assertSame(1, $results['patient_reactivation']['matched_count']);
        $this->assertSame(0, $results['patient_reactivation']['sent_count']);

        $this->assertDatabaseCount('automation_run_logs', 3);
        $this->assertDatabaseHas('automation_run_logs', [
            'automation_type' => 'appointment_reminder',
            'status' => 'preview',
            'matched_count' => 1,
        ]);
    }
}
