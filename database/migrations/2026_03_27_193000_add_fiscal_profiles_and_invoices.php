<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('municipal_registration')->nullable()->after('document');
            $table->string('service_city_code', 20)->nullable()->after('municipal_registration');
            $table->string('nfse_provider_profile', 30)->default('manual')->after('service_city_code');
            $table->string('default_service_code', 30)->nullable()->after('nfse_provider_profile');
            $table->decimal('default_iss_rate', 5, 2)->default(5)->after('default_service_code');
            $table->string('rps_series', 20)->nullable()->after('default_iss_rate');
            $table->string('cnae_code', 20)->nullable()->after('rps_series');
        });

        Schema::create('fiscal_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('account_receivable_id')->constrained('accounts_receivable')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('provider_profile', 30)->default('manual')->index();
            $table->string('city_code', 20)->nullable();
            $table->string('service_code', 30)->nullable();
            $table->string('service_description');
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('deductions_amount', 12, 2)->default(0);
            $table->decimal('tax_base_amount', 12, 2)->default(0);
            $table->decimal('iss_rate', 5, 2)->default(0);
            $table->decimal('iss_amount', 12, 2)->default(0);
            $table->string('rps_series', 20)->nullable();
            $table->string('rps_number', 40)->nullable()->index();
            $table->string('external_reference')->nullable()->index();
            $table->string('municipal_invoice_number')->nullable()->index();
            $table->string('verification_code')->nullable();
            $table->date('issue_date')->nullable();
            $table->dateTime('queued_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status'], 'fiscal_invoice_unit_status_idx');
            $table->index(['account_receivable_id', 'status'], 'fiscal_invoice_receivable_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_invoices', function (Blueprint $table) {
            $table->dropIndex('fiscal_invoice_unit_status_idx');
            $table->dropIndex('fiscal_invoice_receivable_status_idx');
        });

        Schema::dropIfExists('fiscal_invoices');

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'municipal_registration',
                'service_city_code',
                'nfse_provider_profile',
                'default_service_code',
                'default_iss_rate',
                'rps_series',
                'cnae_code',
            ]);
        });
    }
};
