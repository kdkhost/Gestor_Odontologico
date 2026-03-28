<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicalRecord;
use App\Models\DocumentAcceptance;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\ClinicalGovernanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClinicalGovernanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_clinical_governance_snapshot_with_scoping(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 27, 10, 0, 0, config('app.timezone')));

        $unitA = Unit::query()->create([
            'name' => 'Matriz Centro',
            'slug' => 'matriz-centro',
            'is_active' => true,
        ]);

        $unitB = Unit::query()->create([
            'name' => 'Filial Sul',
            'slug' => 'filial-sul',
            'is_active' => true,
        ]);

        $patientWithoutRecord = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Ana Sem Prontuario',
            'is_active' => true,
        ]);

        $patientPlan = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Bruno Tratamento',
            'is_active' => true,
        ]);

        $patientWithDocs = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Carla Completa',
            'is_active' => true,
        ]);

        $patientOtherUnit = Patient::query()->create([
            'unit_id' => $unitB->id,
            'name' => 'Diego Filial',
            'is_active' => true,
        ]);

        $professionalUser = User::query()->create([
            'name' => 'Dra. Paula',
            'email' => 'paula.governanca@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $professional = Professional::query()->create([
            'user_id' => $professionalUser->id,
            'unit_id' => $unitA->id,
            'commission_percentage' => 10,
        ]);

        $appointmentWithoutRecord = Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientWithoutRecord->id,
            'status' => 'completed',
            'origin' => 'admin',
            'scheduled_start' => now()->subDays(2),
            'scheduled_end' => now()->subDays(2)->addHour(),
        ]);

        $appointmentWithRecord = Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientWithDocs->id,
            'status' => 'completed',
            'origin' => 'admin',
            'scheduled_start' => now()->subDay(),
            'scheduled_end' => now()->subDay()->addHour(),
        ]);

        ClinicalRecord::query()->create([
            'appointment_id' => $appointmentWithRecord->id,
            'patient_id' => $patientWithDocs->id,
            'professional_id' => $professional->id,
            'unit_id' => $unitA->id,
            'recorded_at' => now()->subDay(),
        ]);

        Appointment::query()->create([
            'unit_id' => $unitB->id,
            'patient_id' => $patientOtherUnit->id,
            'status' => 'completed',
            'origin' => 'admin',
            'scheduled_start' => now()->subDays(2),
            'scheduled_end' => now()->subDays(2)->addHour(),
        ]);

        $planWithoutFollowUp = TreatmentPlan::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientPlan->id,
            'name' => 'Plano principal',
            'status' => 'approved',
            'total_amount' => 800,
            'final_amount' => 800,
        ]);

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $planWithoutFollowUp->id,
            'description' => 'Sessao de alinhador',
            'status' => 'planned',
            'quantity' => 1,
            'unit_price' => 200,
            'total_price' => 200,
            'scheduled_for' => now()->subDays(5),
        ]);

        $planWithFollowUp = TreatmentPlan::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientWithDocs->id,
            'name' => 'Plano em andamento',
            'status' => 'approved',
            'total_amount' => 500,
            'final_amount' => 500,
        ]);

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $planWithFollowUp->id,
            'description' => 'Revisao',
            'status' => 'planned',
            'quantity' => 1,
            'unit_price' => 150,
            'total_price' => 150,
            'scheduled_for' => now()->addDays(3),
        ]);

        Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientWithDocs->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->addDays(4),
            'scheduled_end' => now()->addDays(4)->addHour(),
        ]);

        DocumentTemplate::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Consentimento 1',
            'slug' => 'consentimento-1-governanca',
            'category' => 'consentimento',
            'channel' => 'portal',
            'body' => '<p>A</p>',
            'is_active' => true,
        ]);

        $acceptedTemplate = DocumentTemplate::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Consentimento 2',
            'slug' => 'consentimento-2-governanca',
            'category' => 'consentimento',
            'channel' => 'portal',
            'body' => '<p>B</p>',
            'is_active' => true,
        ]);

        DocumentAcceptance::query()->create([
            'document_template_id' => $acceptedTemplate->id,
            'patient_id' => $patientWithDocs->id,
            'accepted_at' => now()->subDay(),
            'content_hash' => hash('sha256', 'B'),
            'rendered_content' => 'B',
        ]);

        $snapshot = app(ClinicalGovernanceService::class)->snapshot($unitA->id, limit: 5);

        $this->assertSame('Matriz Centro', $snapshot['scope']['label']);
        $this->assertSame(1, $snapshot['stats']['missing_clinical_records_count']);
        $this->assertSame(1, $snapshot['stats']['plans_without_followup_count']);
        $this->assertSame(2, $snapshot['stats']['pending_documents_count']);
        $this->assertSame(1, $snapshot['stats']['overdue_plan_items_count']);
        $this->assertCount(1, $snapshot['alerts']['completed_without_record']);
        $this->assertSame($appointmentWithoutRecord->id, $snapshot['alerts']['completed_without_record']->first()?->id);
        $this->assertCount(1, $snapshot['alerts']['plans_without_followup']);
        $this->assertSame('Bruno Tratamento', $snapshot['alerts']['plans_without_followup']->first()?->patient?->name);
        $this->assertCount(2, $snapshot['alerts']['pending_required_documents']);
        $this->assertSame('Bruno Tratamento', $snapshot['alerts']['pending_required_documents']->first()?->name);
        $this->assertSame(2, $snapshot['alerts']['pending_required_documents']->first()?->pending_documents_count);
        $this->assertCount(1, $snapshot['alerts']['overdue_treatment_items']);
        $this->assertSame('Sessao de alinhador', $snapshot['alerts']['overdue_treatment_items']->first()?->description);

        Carbon::setTestNow();
    }
}
