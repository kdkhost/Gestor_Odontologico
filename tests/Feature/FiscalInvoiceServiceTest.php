<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\FiscalInvoice;
use App\Models\Patient;
use App\Models\Unit;
use App\Models\User;
use App\Services\FiscalInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FiscalInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_creates_protocols_issues_and_cancels_fiscal_invoice(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Matriz Fiscal',
            'slug' => 'matriz-fiscal',
            'legal_name' => 'Clinica Matriz LTDA',
            'document' => '12.345.678/0001-90',
            'municipal_registration' => '123456',
            'service_city_code' => '3550308',
            'nfse_provider_profile' => 'manual',
            'default_service_code' => '0401',
            'default_iss_rate' => 5,
            'rps_series' => 'A1',
            'cnae_code' => '8630504',
            'is_active' => true,
        ]);

        $staff = User::query()->create([
            'name' => 'Financeiro Fiscal',
            'email' => 'fiscal@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Mariana Fiscal',
            'cpf' => '123.456.789-10',
            'email' => 'mariana@example.com',
            'phone' => '(11) 3333-4444',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'is_active' => true,
        ]);

        $receivable = AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'reference' => 'REC-FISCAL-001',
            'description' => 'Tratamento endodontico',
            'status' => 'paid',
            'total_amount' => 1000,
            'net_amount' => 1000,
            'paid_at' => now()->subDay(),
        ]);

        $service = app(FiscalInvoiceService::class);

        $eligible = $service->eligibleReceivables(
            unitId: $unit->id,
            fromDate: now()->startOfMonth()->toDateString(),
            toDate: now()->endOfMonth()->toDateString(),
        );

        $this->assertCount(1, $eligible);
        $this->assertTrue((bool) $eligible->first()['is_ready']);

        $invoice = $service->createDraftForReceivable($receivable, [
            'created_by_user_id' => $staff->id,
        ]);

        $this->assertDatabaseHas('fiscal_invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
            'amount' => 1000,
            'tax_base_amount' => 1000,
            'iss_rate' => 5,
            'iss_amount' => 50,
            'created_by_user_id' => $staff->id,
        ]);

        $service->queueInvoice($invoice, [
            'rps_number' => 'RPS-000001',
        ]);

        $this->assertDatabaseHas('fiscal_invoices', [
            'id' => $invoice->id,
            'status' => 'pending_submission',
            'rps_number' => 'RPS-000001',
        ]);

        $this->artisan('clinic:nfse-submit', [
            '--unit_id' => $unit->id,
            '--limit' => 10,
        ])->assertExitCode(0);

        $submitted = FiscalInvoice::query()->findOrFail($invoice->id);

        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertNotNull($submitted->external_reference);

        $issued = $service->markAsIssued($submitted, [
            'municipal_invoice_number' => 'NFSE-2026-001',
            'verification_code' => 'ABC123XYZ789',
        ]);

        $this->assertSame('issued', $issued->status);
        $this->assertSame('NFSE-2026-001', $issued->municipal_invoice_number);
        $this->assertSame('ABC123XYZ789', $issued->verification_code);
        $this->assertNotNull($issued->issued_at);

        $cancelled = $service->cancel($issued, [
            'reason' => 'Cancelamento de homologacao.',
        ]);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertSame('Cancelamento de homologacao.', $cancelled->last_error_message);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    public function test_service_blocks_creation_when_unit_fiscal_profile_is_incomplete(): void
    {
        $this->markApplicationAsInstalled();

        $unit = Unit::query()->create([
            'name' => 'Filial sem fiscal',
            'slug' => 'filial-sem-fiscal',
            'legal_name' => 'Clinica Filial LTDA',
            'document' => null,
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Paciente sem nota',
            'is_active' => true,
        ]);

        $receivable = AccountReceivable::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'reference' => 'REC-FISCAL-002',
            'description' => 'Tratamento sem cadastro fiscal',
            'status' => 'paid',
            'total_amount' => 600,
            'net_amount' => 600,
            'paid_at' => now()->subDay(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A unidade nao possui dados fiscais minimos');

        app(FiscalInvoiceService::class)->createDraftForReceivable($receivable);
    }
}
