<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\CommissionSettlement;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use App\Services\CommissionSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_closes_pays_and_reconciles_commission_settlement(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $staff = User::query()->create([
            'name' => 'Financeiro',
            'email' => 'fechamento@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $doctorUser = User::query()->create([
            'name' => 'Dr. Felipe',
            'email' => 'felipe@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $doctorUser->id,
            'unit_id' => $unit->id,
            'commission_percentage' => 10,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Julia Martins',
            'is_active' => true,
        ]);

        $appointment = Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'status' => 'completed',
            'origin' => 'admin',
            'requested_at' => now()->subDays(3),
            'scheduled_start' => now()->subDays(3),
            'scheduled_end' => now()->subDays(3)->addHour(),
            'finished_at' => now()->subDays(3)->addHour(),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'reference' => 'REC-SET-001',
            'description' => 'Tratamento A',
            'status' => 'paid',
            'total_amount' => 500,
            'net_amount' => 500,
            'paid_at' => now()->subDays(2),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(2),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'reference' => 'REC-SET-002',
            'description' => 'Tratamento B',
            'status' => 'paid',
            'total_amount' => 700,
            'net_amount' => 700,
            'paid_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);

        $service = app(CommissionSettlementService::class);

        $candidates = $service->pendingCandidates(
            unitId: $unit->id,
            fromDate: now()->startOfMonth()->toDateString(),
            toDate: now()->endOfMonth()->toDateString(),
        );

        $this->assertCount(1, $candidates);
        $this->assertSame(120.0, (float) $candidates->first()['gross_amount']);

        $settlement = $service->createSettlement(
            professionalId: $professional->id,
            unitId: $unit->id,
            fromDate: now()->startOfMonth()->toDateString(),
            toDate: now()->endOfMonth()->toDateString(),
            createdByUserId: $staff->id,
        );

        $this->assertDatabaseHas('commission_settlements', [
            'id' => $settlement->id,
            'professional_id' => $professional->id,
            'commission_count' => 2,
            'gross_amount' => 120,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('commission_entries', [
            'commission_settlement_id' => $settlement->id,
            'status' => 'batched',
            'amount' => 50,
        ]);

        $service->registerPayment($settlement, [
            'payment_method' => 'pix',
            'payment_reference' => 'PIX-REP-001',
            'proof_path' => 'commission-proofs/comprovante-001.pdf',
            'paid_by_user_id' => $staff->id,
            'notes' => 'Repasse pago via PIX.',
        ]);

        $this->assertDatabaseHas('commission_settlements', [
            'id' => $settlement->id,
            'status' => 'paid',
            'payment_method' => 'pix',
            'payment_reference' => 'PIX-REP-001',
            'proof_path' => 'commission-proofs/comprovante-001.pdf',
            'paid_by_user_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('commission_entries', [
            'commission_settlement_id' => $settlement->id,
            'status' => 'paid',
            'amount' => 70,
        ]);

        $service->markAsReconciled($settlement->fresh(), [
            'bank_statement_reference' => 'EXTRATO-20260327',
            'reconciled_by_user_id' => $staff->id,
            'reconciliation_notes' => 'Conferido com extrato do banco.',
        ]);

        $this->assertDatabaseHas('commission_settlements', [
            'id' => $settlement->id,
            'bank_statement_reference' => 'EXTRATO-20260327',
            'reconciled_by_user_id' => $staff->id,
        ]);

        $storedSettlement = CommissionSettlement::query()->findOrFail($settlement->id);

        $this->assertNotNull($storedSettlement->paid_at);
        $this->assertNotNull($storedSettlement->reconciled_at);
    }
}
