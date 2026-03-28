<?php

namespace Tests\Feature;

use App\Models\InsuranceAuthorization;
use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\InsuranceAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class InsuranceAuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_submits_returns_and_expires_insurance_authorizations(): void
    {
        $this->markApplicationAsInstalled();
        Carbon::setTestNow(Carbon::create(2026, 3, 27, 14, 0, 0, config('app.timezone')));

        $unitA = Unit::query()->create([
            'name' => 'Matriz Convenio',
            'slug' => 'matriz-convenio',
            'is_active' => true,
        ]);

        $unitB = Unit::query()->create([
            'name' => 'Filial Convenio',
            'slug' => 'filial-convenio',
            'is_active' => true,
        ]);

        $insurancePlan = InsurancePlan::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Odonto Prime',
            'code' => 'OP01',
            'ans_registration' => '123456',
            'requires_authorization' => true,
            'authorization_valid_days' => 15,
            'submission_channel' => 'portal',
            'is_active' => true,
        ]);

        InsurancePlan::query()->create([
            'unit_id' => $unitB->id,
            'name' => 'Odonto Filial',
            'code' => 'OF01',
            'requires_authorization' => true,
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Camila Convenio',
            'cpf' => '123.456.789-10',
            'is_active' => true,
        ]);

        $professionalUser = User::query()->create([
            'name' => 'Dr. Convenio',
            'email' => 'convenio@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $professionalUser->id,
            'unit_id' => $unitA->id,
            'commission_percentage' => 10,
        ]);

        $procedure = Procedure::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Implante unitario',
            'code' => 'PROC-IMPL',
            'default_price' => 350,
            'requires_approval' => true,
            'is_active' => true,
        ]);

        $plan = TreatmentPlan::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'insurance_plan_id' => $insurancePlan->id,
            'code' => 'PT-001',
            'name' => 'Plano de implante',
            'status' => 'approved',
            'approved_at' => now()->subDay(),
            'total_amount' => 500,
            'final_amount' => 500,
        ]);

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $plan->id,
            'procedure_id' => $procedure->id,
            'description' => 'Implante superior',
            'status' => 'planned',
            'quantity' => 1,
            'unit_price' => 350,
            'total_price' => 350,
        ]);

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $plan->id,
            'procedure_id' => $procedure->id,
            'description' => 'Protese provisoria',
            'status' => 'planned',
            'quantity' => 1,
            'unit_price' => 150,
            'total_price' => 150,
        ]);

        $otherPlan = TreatmentPlan::query()->create([
            'unit_id' => $unitB->id,
            'patient_id' => Patient::query()->create([
                'unit_id' => $unitB->id,
                'name' => 'Paciente Filial',
                'is_active' => true,
            ])->id,
            'insurance_plan_id' => InsurancePlan::query()->where('unit_id', $unitB->id)->firstOrFail()->id,
            'name' => 'Plano filial',
            'status' => 'approved',
            'total_amount' => 200,
            'final_amount' => 200,
        ]);

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $otherPlan->id,
            'description' => 'Item filial',
            'status' => 'planned',
            'quantity' => 1,
            'unit_price' => 200,
            'total_price' => 200,
        ]);

        $service = app(InsuranceAuthorizationService::class);

        $candidates = $service->candidateTreatmentPlans($unitA->id);

        $this->assertCount(1, $candidates);
        $this->assertSame($plan->id, $candidates->first()['treatment_plan_id']);
        $this->assertSame(2, $candidates->first()['eligible_items_count']);

        $draft = $service->createDraft($plan, [
            'created_by_user_id' => $professionalUser->id,
        ]);

        $this->assertDatabaseHas('insurance_authorizations', [
            'id' => $draft->id,
            'unit_id' => $unitA->id,
            'patient_id' => $patient->id,
            'status' => 'draft',
            'requested_total' => 500,
            'submission_channel' => 'portal',
        ]);
        $this->assertCount(2, $draft->items);

        $submitted = $service->submit($draft, [
            'external_guide_number' => 'GUIA-OPER-001',
        ]);

        $this->assertSame('submitted', $submitted->status);
        $this->assertSame('GUIA-OPER-001', $submitted->external_guide_number);
        $this->assertNotNull($submitted->submitted_at);

        $returned = $service->registerResponse($submitted, [
            [
                'id' => $submitted->items[0]->id,
                'status' => 'authorized',
                'authorized_quantity' => 1,
                'authorized_amount' => 350,
            ],
            [
                'id' => $submitted->items[1]->id,
                'status' => 'denied',
                'denial_reason' => 'Cobertura parcial do plano.',
            ],
        ]);

        $this->assertSame('partially_authorized', $returned->status);
        $this->assertSame('350.00', (string) $returned->authorized_total);
        $this->assertNotNull($returned->authorization_number);
        $this->assertCount(1, $returned->items->where('status', 'authorized'));
        $this->assertCount(1, $returned->items->where('status', 'denied'));

        $summary = $service->summary($unitA->id);

        $this->assertSame(0, $summary['draft_count']);
        $this->assertSame(0, $summary['submitted_count']);
        $this->assertSame(1, $summary['authorized_to_schedule_count']);
        $this->assertSame(1, $summary['denied_items_count']);
        $this->assertSame(350.0, $summary['authorized_total']);

        $payload = $service->exportPayload($returned);

        $this->assertSame('tiss-ready-internal-v1', $payload['schema']);
        $this->assertSame('Camila Convenio', $payload['patient']['name']);
        $this->assertSame('Odonto Prime', $payload['insurance_plan']['name']);
        $this->assertCount(2, $payload['items']);

        $returned->update([
            'valid_until' => now()->subHour(),
        ]);
        $returned->items()
            ->where('status', 'authorized')
            ->update(['valid_until' => now()->subHour()]);

        $expiredCount = $service->markExpired($unitA->id);

        $this->assertSame(1, $expiredCount);
        $this->assertSame('expired', InsuranceAuthorization::query()->findOrFail($returned->id)->status);

        Carbon::setTestNow();
    }

    public function test_it_blocks_draft_creation_when_plan_does_not_require_authorization(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Unidade sem guia',
            'slug' => 'unidade-sem-guia',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente sem autorizacao',
            'is_active' => true,
        ]);

        $insurancePlan = InsurancePlan::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Convenio livre',
            'code' => 'CL01',
            'requires_authorization' => false,
            'is_active' => true,
        ]);

        $procedure = Procedure::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Limpeza',
            'code' => 'PROC-LIMP',
            'default_price' => 120,
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $plan = TreatmentPlan::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'insurance_plan_id' => $insurancePlan->id,
            'name' => 'Plano simples',
            'status' => 'approved',
            'total_amount' => 120,
            'final_amount' => 120,
        ]);

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $plan->id,
            'procedure_id' => $procedure->id,
            'description' => 'Limpeza simples',
            'status' => 'planned',
            'quantity' => 1,
            'unit_price' => 120,
            'total_price' => 120,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Este plano nao exige autorizacao de convenio.');

        app(InsuranceAuthorizationService::class)->createDraft($plan);
    }
}
