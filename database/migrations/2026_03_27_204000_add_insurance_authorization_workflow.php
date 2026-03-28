<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->string('ans_registration', 30)->nullable()->after('code');
            $table->string('operator_document', 20)->nullable()->after('ans_registration');
            $table->boolean('requires_authorization')->default(false)->after('grace_days');
            $table->integer('authorization_valid_days')->default(30)->after('requires_authorization');
            $table->integer('settlement_days')->default(30)->after('authorization_valid_days');
            $table->string('submission_channel', 30)->nullable()->after('settlement_days');
            $table->string('tiss_table_code', 30)->nullable()->after('submission_channel');
        });

        Schema::create('insurance_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('insurance_plan_id')->nullable()->constrained('insurance_plans')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('treatment_plans')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('authorization_number')->nullable()->index();
            $table->string('external_guide_number')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->string('submission_channel', 30)->default('manual');
            $table->decimal('requested_total', 12, 2)->default(0);
            $table->decimal('authorized_total', 12, 2)->default(0);
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->dateTime('response_due_at')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->text('last_status_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_authorization_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_authorization_id')->constrained('insurance_authorizations')->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->string('description');
            $table->string('tooth_code', 10)->nullable();
            $table->string('face', 10)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('requested_quantity', 10, 2)->default(1);
            $table->decimal('authorized_quantity', 10, 2)->default(0);
            $table->decimal('requested_amount', 12, 2)->default(0);
            $table->decimal('authorized_amount', 12, 2)->default(0);
            $table->text('denial_reason')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_authorization_items');
        Schema::dropIfExists('insurance_authorizations');

        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->dropColumn([
                'ans_registration',
                'operator_document',
                'requires_authorization',
                'authorization_valid_days',
                'settlement_days',
                'submission_channel',
                'tiss_table_code',
            ]);
        });
    }
};
