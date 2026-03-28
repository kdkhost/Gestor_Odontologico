<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\DocumentAcceptance;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\PatientGuardian;
use App\Models\PwaSubscription;
use App\Models\Unit;
use App\Models\User;
use App\Services\PrivacyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_patient_data_to_a_protected_json_package(): void
    {
        $this->markApplicationAsInstalled();
        Storage::fake('local');

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $staff = User::query()->create([
            'name' => 'Controlador LGPD',
            'email' => 'lgpd@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $portalUser = User::query()->create([
            'name' => 'Ana Souza',
            'email' => 'ana.portal@example.com',
            'phone' => '11999990001',
            'document' => '12345678901',
            'user_type' => 'patient',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'user_id' => $portalUser->id,
            'name' => 'Ana Souza',
            'cpf' => '123.456.789-01',
            'email' => 'ana@example.com',
            'phone' => '11999990001',
            'whatsapp' => '11999990001',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'is_active' => true,
        ]);

        PatientGuardian::query()->create([
            'patient_id' => $patient->id,
            'name' => 'Carlos Souza',
            'document' => '98765432100',
            'email' => 'carlos@example.com',
            'phone' => '11999990002',
            'whatsapp' => '11999990002',
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'origin' => 'portal',
            'scheduled_start' => now()->subDays(3),
            'scheduled_end' => now()->subDays(3)->addHour(),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'description' => 'Tratamento restaurador',
            'status' => 'paid',
            'total_amount' => 450,
            'net_amount' => 450,
            'paid_at' => now()->subDays(2),
        ]);

        $template = DocumentTemplate::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Consentimento geral',
            'slug' => 'consentimento-geral-lgpd',
            'category' => 'consentimento',
            'channel' => 'portal',
            'body' => '<p>Documento</p>',
            'is_active' => true,
        ]);

        DocumentAcceptance::query()->create([
            'document_template_id' => $template->id,
            'patient_id' => $patient->id,
            'accepted_at' => now()->subDays(2),
            'content_hash' => hash('sha256', 'Documento'),
            'rendered_content' => 'Documento',
        ]);

        PwaSubscription::query()->create([
            'patient_id' => $patient->id,
            'endpoint' => 'https://push.example.com/token-123',
            'public_key' => 'public',
            'auth_token' => 'auth',
            'is_active' => true,
        ]);

        $service = app(PrivacyManagementService::class);

        $request = $service->createRequest($patient, 'export', [
            'requested_by_user_id' => $staff->id,
        ]);

        $processed = $service->processRequest($request, [
            'processed_by_user_id' => $staff->id,
        ]);

        $this->assertSame('completed', $processed->status);
        $this->assertNotNull($processed->export_path);
        Storage::disk('local')->assertExists($processed->export_path);
        $this->assertNotNull($patient->fresh()->privacy_last_exported_at);

        $payload = json_decode(Storage::disk('local')->get($processed->export_path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Ana Souza', $payload['patient']['name']);
        $this->assertCount(1, $payload['guardians']);
        $this->assertCount(1, $payload['appointments']);
        $this->assertCount(1, $payload['accounts_receivable']);
        $this->assertCount(1, $payload['document_acceptances']);
    }

    public function test_it_anonymizes_sensitive_patient_registration_data_and_keeps_legal_modules_recorded(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Filial Norte',
            'slug' => 'filial-norte',
            'is_active' => true,
        ]);

        $staff = User::query()->create([
            'name' => 'DPO Interno',
            'email' => 'dpo@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $portalUser = User::query()->create([
            'name' => 'Marcos Lima',
            'email' => 'marcos.portal@example.com',
            'phone' => '11988887777',
            'document' => '55544433322',
            'user_type' => 'patient',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'user_id' => $portalUser->id,
            'name' => 'Marcos Lima',
            'cpf' => '555.444.333-22',
            'email' => 'marcos@example.com',
            'phone' => '11988887777',
            'whatsapp' => '11988887777',
            'street' => 'Rua A',
            'number' => '100',
            'district' => 'Centro',
            'city' => 'Campinas',
            'state' => 'SP',
            'is_active' => true,
            'whatsapp_opt_in' => true,
        ]);

        $guardian = PatientGuardian::query()->create([
            'patient_id' => $patient->id,
            'name' => 'Responsavel Original',
            'document' => '22211100099',
            'email' => 'responsavel@example.com',
            'phone' => '11999994444',
            'whatsapp' => '11999994444',
            'address' => ['city' => 'Campinas'],
            'notes' => 'Observacao sensivel',
        ]);

        PwaSubscription::query()->create([
            'patient_id' => $patient->id,
            'endpoint' => 'https://push.example.com/token-999',
            'public_key' => 'public',
            'auth_token' => 'auth',
            'is_active' => true,
        ]);

        $service = app(PrivacyManagementService::class);

        $request = $service->createRequest($patient, 'anonymize', [
            'requested_by_user_id' => $staff->id,
        ]);

        $processed = $service->processRequest($request, [
            'processed_by_user_id' => $staff->id,
        ]);

        $patient = $patient->fresh();
        $guardian = $guardian->fresh();
        $portalUser = $portalUser->fresh();

        $this->assertSame('completed', $processed->status);
        $this->assertStringContainsString('Paciente anonimizado', $patient->name);
        $this->assertNull($patient->cpf);
        $this->assertNull($patient->email);
        $this->assertNull($patient->phone);
        $this->assertNull($patient->whatsapp);
        $this->assertFalse($patient->is_active);
        $this->assertNotNull($patient->anonymized_at);
        $this->assertSame("Responsavel anonimizado #{$guardian->id}", $guardian->name);
        $this->assertNull($guardian->email);
        $this->assertNull($guardian->phone);
        $this->assertSame($patient->name, $portalUser->name);
        $this->assertNull($portalUser->email);
        $this->assertFalse((bool) $portalUser->is_active);
        $this->assertDatabaseCount('pwa_subscriptions', 0);
        $this->assertContains('financial_records', $processed->result_snapshot['retained_modules']);
    }
}
