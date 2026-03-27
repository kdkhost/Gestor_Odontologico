<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\BankStatementLine;
use App\Models\CommissionSettlement;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use App\Services\BankStatementImportService;
use App\Services\CommissionSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankStatementImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_service_suggests_and_reconciles_statement_lines(): void
    {
        $this->markApplicationAsInstalled();
        Storage::fake('local');

        $unit = Unit::query()->create([
            'name' => 'Matriz',
            'slug' => 'matriz',
            'is_active' => true,
        ]);

        $staff = User::query()->create([
            'name' => 'Financeiro',
            'email' => 'importacao@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $doctorUser = User::query()->create([
            'name' => 'Dr. Felipe',
            'email' => 'felipe.import@example.com',
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
            'name' => 'Paciente Extrato',
            'is_active' => true,
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
            'reference' => 'REC-EXT-001',
            'description' => 'Tratamento extrato',
            'status' => 'paid',
            'total_amount' => 1200,
            'net_amount' => 1200,
            'paid_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);

        $settlement = app(CommissionSettlementService::class)->createSettlement(
            professionalId: $professional->id,
            unitId: $unit->id,
            fromDate: now()->startOfMonth()->toDateString(),
            toDate: now()->endOfMonth()->toDateString(),
            createdByUserId: $staff->id,
        );

        app(CommissionSettlementService::class)->registerPayment($settlement, [
            'payment_method' => 'pix',
            'payment_reference' => 'PIX-REP-IMPORT',
            'paid_by_user_id' => $staff->id,
        ]);

        $statementDate = now()->format('d/m/Y');
        $csv = "data;descricao;valor;referencia\n{$statementDate};Repasse Dr. Felipe PIX-REP-IMPORT;-120,00;PIX-REP-IMPORT\n";

        $storedPath = 'statement-imports/extrato-repasse.csv';
        Storage::disk('local')->put($storedPath, $csv);

        $service = app(BankStatementImportService::class);

        $import = $service->importStoredFile(
            storedPath: $storedPath,
            originalName: 'extrato-repasse.csv',
            unitId: $unit->id,
            uploadedByUserId: $staff->id,
            options: [
                'disk' => 'local',
                'delimiter' => 'auto',
                'has_header' => true,
            ],
        );

        $this->assertSame('processed', $import->status);
        $this->assertSame(1, $import->total_lines);
        $this->assertSame(1, $import->matched_suggestions_count);
        $this->assertSame(0, $import->unmatched_lines_count);

        $line = BankStatementLine::query()->firstOrFail();

        $this->assertSame($settlement->id, $line->suggested_commission_settlement_id);
        $this->assertGreaterThanOrEqual(60, (int) $line->match_score);
        $this->assertCount(1, $service->openSuggestions($unit->id));

        $reconciledCount = $service->reconcileImportSuggestions($import->fresh(), $staff->id);

        $this->assertSame(1, $reconciledCount);
        $this->assertDatabaseHas('bank_statement_lines', [
            'id' => $line->id,
            'matched_commission_settlement_id' => $settlement->id,
        ]);

        $storedSettlement = CommissionSettlement::query()->findOrFail($settlement->id);

        $this->assertNotNull($storedSettlement->reconciled_at);
        $this->assertSame('PIX-REP-IMPORT', $storedSettlement->bank_statement_reference);
    }

    public function test_import_service_parses_ofx_with_bank_profile_metadata(): void
    {
        $this->markApplicationAsInstalled();
        Storage::fake('local');

        $unit = Unit::query()->create([
            'name' => 'Filial Sul',
            'slug' => 'filial-sul',
            'is_active' => true,
        ]);

        $staff = User::query()->create([
            'name' => 'Financeiro OFX',
            'email' => 'ofx@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $doctorUser = User::query()->create([
            'name' => 'Dra. Camila',
            'email' => 'camila.ofx@example.com',
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
            'name' => 'Paciente OFX',
            'is_active' => true,
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
            'reference' => 'REC-OFX-001',
            'description' => 'Tratamento OFX',
            'status' => 'paid',
            'total_amount' => 900,
            'net_amount' => 900,
            'paid_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);

        $settlement = app(CommissionSettlementService::class)->createSettlement(
            professionalId: $professional->id,
            unitId: $unit->id,
            fromDate: now()->startOfMonth()->toDateString(),
            toDate: now()->endOfMonth()->toDateString(),
            createdByUserId: $staff->id,
        );

        app(CommissionSettlementService::class)->registerPayment($settlement, [
            'payment_method' => 'pix',
            'payment_reference' => 'PIX-OFX-001',
            'paid_by_user_id' => $staff->id,
        ]);

        $ofxDate = now()->format('YmdHis');
        $ofx = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>{$ofxDate}
<TRNAMT>-90.00
<FITID>PIX-OFX-001
<NAME>Repasse Dra. Camila
<MEMO>Pagamento PIX PIX-OFX-001
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $storedPath = 'statement-imports/extrato.ofx';
        Storage::disk('local')->put($storedPath, $ofx);

        $service = app(BankStatementImportService::class);

        $import = $service->importStoredFile(
            storedPath: $storedPath,
            originalName: 'extrato.ofx',
            unitId: $unit->id,
            uploadedByUserId: $staff->id,
            options: [
                'disk' => 'local',
                'file_type' => 'ofx',
                'bank_profile' => 'itau',
                'delimiter' => 'auto',
                'has_header' => false,
            ],
        );

        $line = $import->lines()->firstOrFail();

        $this->assertSame('ofx', $import->file_type);
        $this->assertSame('itau', $import->bank_profile);
        $this->assertSame('ofx', $import->meta['file_type']);
        $this->assertSame('itau', $import->meta['bank_profile']);
        $this->assertSame('ofx', $import->meta['parser']);
        $this->assertSame($settlement->id, $line->suggested_commission_settlement_id);
        $this->assertSame('PIX-OFX-001', $line->transaction_reference);
        $this->assertStringContainsString('Pagamento PIX', (string) $line->description);
    }
}
