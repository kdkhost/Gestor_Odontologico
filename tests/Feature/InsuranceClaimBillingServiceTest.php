<?php

namespace Tests\Feature;

use App\Models\InsuranceAuthorization;
use App\Models\InsuranceAuthorizationItem;
use App\Models\InsuranceClaimBatch;
use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\InsuranceClaimBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class InsuranceClaimBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_claim_batches_processes_operator_return_and_representations(): void
    {
        $this->markApplicationAsInstalled();
        Carbon::setTestNow(Carbon::create(2026, 3, 27, 16, 0, 0, config('app.timezone')));

        $unit = Unit::query()->create([
            'name' => 'Matriz Faturamento',
            'slug' => 'matriz-faturamento',
            'is_active' => true,
        ]);

        $insurancePlan = InsurancePlan::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Operadora Premium',
            'code' => 'OPR01',
            'ans_registration' => '123456',
            'operator_document' => '12.345.678/0001-90',
            'tiss_table_code' => '22',
            'requires_authorization' => true,
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Ricardo Conveniado',
            'cpf' => '123.456.789-10',
            'is_active' => true,
        ]);

        $professionalUser = User::query()->create([
            'name' => 'Dra. Fatima',
            'email' => 'fatima.faturamento@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $professionalUser->id,
            'unit_id' => $unit->id,
            'commission_percentage' => 10,
        ]);

        $procedure = Procedure::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Cirurgia guiada',
            'code' => 'PROC-CIR',
            'default_price' => 500,
            'requires_approval' => true,
            'is_active' => true,
        ]);

        $plan = TreatmentPlan::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'insurance_plan_id' => $insurancePlan->id,
            'code' => 'PLN-CLAIM-001',
            'name' => 'Plano faturavel',
            'status' => 'approved',
            'approved_at' => now()->subDays(2),
            'total_amount' => 500,
            'final_amount' => 500,
        ]);

        $planItemA = TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $plan->id,
            'procedure_id' => $procedure->id,
            'description' => 'Sessao autorizada 1',
            'status' => 'done',
            'quantity' => 1,
            'unit_price' => 300,
            'total_price' => 300,
            'completed_at' => now()->subDay(),
        ]);

        $planItemB = TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $plan->id,
            'procedure_id' => $procedure->id,
            'description' => 'Sessao autorizada 2',
            'status' => 'done',
            'quantity' => 1,
            'unit_price' => 200,
            'total_price' => 200,
            'completed_at' => now()->subDay(),
        ]);

        $authorization = InsuranceAuthorization::query()->create([
            'unit_id' => $unit->id,
            'insurance_plan_id' => $insurancePlan->id,
            'patient_id' => $patient->id,
            'treatment_plan_id' => $plan->id,
            'professional_id' => $professional->id,
            'created_by_user_id' => $professionalUser->id,
            'reference' => 'GUIA-AUT-001',
            'authorization_number' => 'AUT-2026-001',
            'status' => 'authorized',
            'submission_channel' => 'portal',
            'requested_total' => 500,
            'authorized_total' => 500,
            'authorized_at' => now()->subDays(2),
            'valid_until' => now()->addDays(20),
        ]);

        InsuranceAuthorizationItem::query()->create([
            'insurance_authorization_id' => $authorization->id,
            'treatment_plan_item_id' => $planItemA->id,
            'procedure_id' => $procedure->id,
            'description' => 'Item faturavel 1',
            'status' => 'authorized',
            'requested_quantity' => 1,
            'authorized_quantity' => 1,
            'requested_amount' => 300,
            'authorized_amount' => 300,
        ]);

        InsuranceAuthorizationItem::query()->create([
            'insurance_authorization_id' => $authorization->id,
            'treatment_plan_item_id' => $planItemB->id,
            'procedure_id' => $procedure->id,
            'description' => 'Item faturavel 2',
            'status' => 'authorized',
            'requested_quantity' => 1,
            'authorized_quantity' => 1,
            'requested_amount' => 200,
            'authorized_amount' => 200,
        ]);

        $service = app(InsuranceClaimBillingService::class);
        $competence = now()->format('Y-m');

        $groups = $service->pendingExecutionGroups($unit->id);

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups->first()['eligible_items_count']);
        $this->assertSame(500.0, $groups->first()['claimed_total']);

        $batch = $service->createDraftBatch(
            insurancePlanId: $insurancePlan->id,
            competenceMonth: $competence,
            unitId: $unit->id,
            createdByUserId: $professionalUser->id,
        );

        $this->assertDatabaseHas('insurance_claim_batches', [
            'id' => $batch->id,
            'status' => 'draft',
            'batch_type' => 'initial',
            'guide_count' => 1,
            'claimed_total' => 500,
        ]);

        $submitted = $service->submitBatch($batch, [
            'submitted_by_user_id' => $professionalUser->id,
        ]);

        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertNotNull($submitted->batch_number);

        $submitted->load('guides.items');
        $items = $submitted->guides->flatMap(fn ($guide) => $guide->items)->values();

        $returned = $service->registerBatchReturn($submitted, [
            [
                'id' => $items[0]->id,
                'approved_quantity' => 1,
                'approved_amount' => 300,
                'received_amount' => 300,
            ],
            [
                'id' => $items[1]->id,
                'approved_quantity' => 0,
                'approved_amount' => 0,
                'received_amount' => 0,
                'gloss_reason' => 'Glosa total para reapresentacao.',
            ],
        ]);

        $this->assertSame('partial_gloss', $returned->status);
        $this->assertSame('300.00', (string) $returned->received_total);
        $this->assertSame('200.00', (string) $returned->gloss_total);

        $representation = $service->createRepresentationBatch(
            sourceBatch: InsuranceClaimBatch::query()->with('guides.items.representations', 'guides.items.authorizationItem.authorization')->findOrFail($returned->id),
            createdByUserId: $professionalUser->id,
        );

        $this->assertSame('representation', $representation->batch_type);
        $this->assertSame('draft', $representation->status);
        $this->assertSame('200.00', (string) $representation->claimed_total);

        $payload = $service->exportPayload($representation);

        $this->assertSame('tiss-billing-ready-internal-v1', $payload['schema']);
        $this->assertSame('Operadora Premium', $payload['insurance_plan']['name']);
        $this->assertCount(1, $payload['guides']);
        $this->assertCount(1, $payload['guides'][0]['items']);

        $summary = $service->summary($unit->id);

        $this->assertSame(0, $summary['pending_billing_count']);
        $this->assertSame(1, $summary['draft_batches_count']);
        $this->assertSame(0, $summary['glossed_items_count']);
        $this->assertSame(0, $summary['representation_candidates_count']);
        $this->assertSame(300.0, $summary['received_total']);
        $this->assertSame(200.0, $summary['gloss_total']);

        Carbon::setTestNow();
    }

    public function test_it_blocks_batch_creation_when_there_is_nothing_executed_to_bill(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Unidade sem execucao',
            'slug' => 'unidade-sem-execucao',
            'is_active' => true,
        ]);

        $insurancePlan = InsurancePlan::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Operadora vazia',
            'code' => 'OPZ01',
            'requires_authorization' => true,
            'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nao existem itens elegiveis para faturar neste convenio e competencia.');

        app(InsuranceClaimBillingService::class)->createDraftBatch(
            insurancePlanId: $insurancePlan->id,
            competenceMonth: now()->format('Y-m'),
            unitId: $unit->id,
        );
    }
}
