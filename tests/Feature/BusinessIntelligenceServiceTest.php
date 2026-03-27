<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PerformanceTarget;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use App\Services\BusinessIntelligenceService;
use App\Services\CommissionSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessIntelligenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_returns_summary_targets_professional_leaderboard_and_settlement_metrics(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Dr. Renato',
            'email' => 'renato@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'commission_percentage' => 10,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Mariana Alves',
            'is_active' => true,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $appointment = Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'status' => 'completed',
            'origin' => 'admin',
            'requested_at' => now()->subDays(2),
            'scheduled_start' => now()->subDays(2),
            'scheduled_end' => now()->subDays(2)->addHour(),
            'finished_at' => now()->subDays(2)->addHour(),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'reference' => 'REC-BI-001',
            'description' => 'Plano clínico',
            'status' => 'paid',
            'total_amount' => 1000,
            'net_amount' => 1000,
            'paid_at' => now()->subDay(),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDay(),
        ]);

        PerformanceTarget::query()->create([
            'unit_id' => $unit->id,
            'metric' => 'revenue_received',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'target_value' => 2000,
            'is_active' => true,
        ]);

        PerformanceTarget::query()->create([
            'unit_id' => $unit->id,
            'professional_id' => $professional->id,
            'metric' => 'completed_appointments',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'target_value' => 2,
            'is_active' => true,
        ]);

        $settlementService = app(CommissionSettlementService::class);
        $settlement = $settlementService->createSettlement(
            professionalId: $professional->id,
            unitId: $unit->id,
            fromDate: now()->startOfMonth()->toDateString(),
            toDate: now()->endOfMonth()->toDateString(),
            createdByUserId: $user->id,
        );

        $settlementService->registerPayment($settlement, [
            'payment_method' => 'pix',
            'payment_reference' => 'PIX-BI-001',
            'paid_by_user_id' => $user->id,
        ]);

        $settlementService->markAsReconciled($settlement->fresh(), [
            'bank_statement_reference' => 'EXTRATO-BI-001',
            'reconciled_by_user_id' => $user->id,
        ]);

        $snapshot = app(BusinessIntelligenceService::class)->snapshot(
            unitId: $unit->id,
            fromDate: now()->subDays(30)->toDateString(),
            toDate: now()->toDateString(),
        );

        $this->assertSame('Matriz', $snapshot['scope']['label']);
        $this->assertSame(1000.0, $snapshot['summary']['revenue_received']);
        $this->assertSame(1, $snapshot['summary']['completed_appointments']);
        $this->assertSame(1, $snapshot['summary']['new_patients']);
        $this->assertSame(100.0, $snapshot['summary']['commission_generated']);
        $this->assertSame(100.0, $snapshot['summary']['commission_paid']);
        $this->assertSame(100.0, $snapshot['summary']['settlements_paid']);
        $this->assertSame(100.0, $snapshot['summary']['settlements_reconciled']);
        $this->assertSame(0.0, $snapshot['summary']['settlements_pending_reconciliation']);
        $this->assertCount(1, $snapshot['targets']);
        $this->assertSame(50.0, $snapshot['targets'][0]['progress']);
        $this->assertCount(1, $snapshot['professional_targets']);
        $this->assertSame('professional', $snapshot['professional_targets'][0]['scope_type']);
        $this->assertSame('Dr. Renato', $snapshot['professional_targets'][0]['scope_label']);
        $this->assertSame(50.0, $snapshot['professional_targets'][0]['progress']);
        $this->assertSame('Dr. Renato', $snapshot['professionals']->first()['professional_name']);
    }
}
