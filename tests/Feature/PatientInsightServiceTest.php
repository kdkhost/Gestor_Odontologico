<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\DocumentAcceptance;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\Unit;
use App\Services\PatientInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientInsightServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_patient_360_snapshot_with_attention_level(): void
    {
        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Luciana Alves',
            'phone' => '11999998888',
            'whatsapp' => '11999998888',
            'email' => 'luciana@example.com',
            'last_visit_at' => now()->subDays(140),
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'no_show',
            'origin' => 'phone',
            'scheduled_start' => now()->subDays(20),
            'scheduled_end' => now()->subDays(20)->addHour(),
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'no_show',
            'origin' => 'admin',
            'scheduled_start' => now()->subDays(70),
            'scheduled_end' => now()->subDays(70)->addHour(),
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'origin' => 'admin',
            'scheduled_start' => now()->subDays(150),
            'scheduled_end' => now()->subDays(150)->addHour(),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'description' => 'Tratamento ortodôntico',
            'status' => 'overdue',
            'total_amount' => 1200,
            'net_amount' => 1200,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        TreatmentPlan::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'name' => 'Plano principal',
            'status' => 'approved',
            'total_amount' => 1400,
            'final_amount' => 1200,
        ]);

        $acceptedTemplate = DocumentTemplate::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Consentimento 1',
            'slug' => 'consentimento-1',
            'category' => 'consentimento',
            'channel' => 'portal',
            'body' => '<p>Documento A</p>',
            'is_active' => true,
        ]);

        DocumentTemplate::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Consentimento 2',
            'slug' => 'consentimento-2',
            'category' => 'consentimento',
            'channel' => 'portal',
            'body' => '<p>Documento B</p>',
            'is_active' => true,
        ]);

        DocumentAcceptance::query()->create([
            'document_template_id' => $acceptedTemplate->id,
            'patient_id' => $patient->id,
            'accepted_at' => now()->subDays(100),
            'content_hash' => hash('sha256', 'Documento A'),
            'rendered_content' => 'Documento A',
        ]);

        $snapshot = app(PatientInsightService::class)->snapshot($patient);

        $this->assertSame('Luciana Alves', $snapshot['identity']['name']);
        $this->assertSame('critico', $snapshot['attention']['level']);
        $this->assertGreaterThanOrEqual(3, count($snapshot['attention']['reasons']));
        $this->assertSame(1200.0, $snapshot['summary']['open_balance']);
        $this->assertSame(1200.0, $snapshot['summary']['overdue_balance']);
        $this->assertSame(2, $snapshot['summary']['no_show_count']);
        $this->assertSame(1, $snapshot['summary']['pending_documents_count']);
        $this->assertSame(1, $snapshot['summary']['active_treatment_plans']);
        $this->assertCount(3, $snapshot['recent_appointments']);
        $this->assertCount(1, $snapshot['open_receivables']);
        $this->assertCount(1, $snapshot['document_acceptances']);
    }
}
