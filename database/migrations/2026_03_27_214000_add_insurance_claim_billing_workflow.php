<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claim_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('insurance_plan_id')->constrained('insurance_plans')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('batch_type', 30)->default('initial')->index();
            $table->string('competence_month', 7)->index();
            $table->string('batch_number')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->string('tiss_version', 20)->default('4.01.00');
            $table->unsignedInteger('guide_count')->default(0);
            $table->decimal('claimed_total', 12, 2)->default(0);
            $table->decimal('approved_total', 12, 2)->default(0);
            $table->decimal('received_total', 12, 2)->default(0);
            $table->decimal('gloss_total', 12, 2)->default(0);
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('last_status_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_claim_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_claim_batch_id')->constrained('insurance_claim_batches')->cascadeOnDelete();
            $table->foreignId('insurance_authorization_id')->nullable()->constrained('insurance_authorizations')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('treatment_plans')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('guide_type', 30)->default('sp_sadt');
            $table->string('status', 30)->default('draft')->index();
            $table->string('authorization_number')->nullable()->index();
            $table->string('external_guide_number')->nullable()->index();
            $table->decimal('claimed_total', 12, 2)->default(0);
            $table->decimal('approved_total', 12, 2)->default(0);
            $table->decimal('received_total', 12, 2)->default(0);
            $table->decimal('gloss_total', 12, 2)->default(0);
            $table->dateTime('executed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_claim_guide_id')->constrained('insurance_claim_guides')->cascadeOnDelete();
            $table->foreignId('insurance_authorization_item_id')->nullable()->constrained('insurance_authorization_items')->nullOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->foreignId('represented_from_claim_item_id')->nullable()->constrained('insurance_claim_items')->nullOnDelete();
            $table->string('description');
            $table->string('status', 30)->default('draft')->index();
            $table->dateTime('executed_at')->nullable();
            $table->decimal('claimed_quantity', 10, 2)->default(0);
            $table->decimal('approved_quantity', 10, 2)->default(0);
            $table->decimal('claimed_amount', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->default(0);
            $table->decimal('received_amount', 12, 2)->default(0);
            $table->decimal('gloss_amount', 12, 2)->default(0);
            $table->text('gloss_reason')->nullable();
            $table->dateTime('represented_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claim_items');
        Schema::dropIfExists('insurance_claim_guides');
        Schema::dropIfExists('insurance_claim_batches');
    }
};
