<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('document', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
        });

        Schema::create('chairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('name');
            $table->string('room')->nullable();
            $table->string('color', 20)->default('#0f766e');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->text('coverage_notes')->nullable();
            $table->decimal('default_discount_percentage', 5, 2)->default(0);
            $table->integer('grace_days')->default(0);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('preferred_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('cpf', 14)->nullable()->unique();
            $table->string('rg', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('occupation')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->text('allergies')->nullable();
            $table->text('continuous_medication')->nullable();
            $table->text('observations')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('patient_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('document', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->json('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('license_type', 20)->default('CRO');
            $table->string('license_number', 50)->nullable();
            $table->string('specialty')->nullable();
            $table->string('agenda_color', 20)->default('#2563eb');
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->json('settings')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('access_schedule_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('role_name')->nullable();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('allow_outside_window')->default(false);
            $table->json('allowed_ip_list')->nullable();
            $table->json('allowed_device_hashes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->string('category')->nullable();
            $table->decimal('default_price', 12, 2)->default(0);
            $table->integer('default_duration_minutes')->default(60);
            $table->boolean('requires_approval')->default(false);
            $table->json('consumption_rules')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('chair_id')->nullable()->constrained('chairs')->nullOnDelete();
            $table->foreignId('insurance_plan_id')->nullable()->constrained('insurance_plans')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('requested')->index();
            $table->string('origin', 30)->default('portal')->index();
            $table->string('confirmation_channel', 30)->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('anamnesis')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->text('chief_complaint')->nullable();
            $table->json('anamnesis')->nullable();
            $table->text('diagnosis')->nullable();
            $table->longText('evolution')->nullable();
            $table->longText('prescription')->nullable();
            $table->longText('certificate_text')->nullable();
            $table->longText('service_order')->nullable();
            $table->json('attachments')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('odontogram_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('clinical_record_id')->nullable()->constrained('clinical_records')->nullOnDelete();
            $table->string('tooth_code', 10);
            $table->string('face', 10)->nullable();
            $table->string('condition', 50);
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();
        });

        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('insurance_plan_id')->nullable()->constrained('insurance_plans')->nullOnDelete();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('status', 30)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->string('tooth_code', 10)->nullable();
            $table->string('face', 10)->nullable();
            $table->string('description');
            $table->string('status', 30)->default('planned');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->json('inventory_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 50)->default('consentimento');
            $table->string('channel', 30)->default('portal');
            $table->string('subject')->nullable();
            $table->json('variables')->nullable();
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('content_hash', 64);
            $table->longText('rendered_content');
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('treatment_plans')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('insurance_plan_id')->nullable()->constrained('insurance_plans')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable()->unique();
            $table->string('description');
            $table->string('status', 30)->default('open')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_receivable_id')->constrained('accounts_receivable')->cascadeOnDelete();
            $table->unsignedInteger('installment_number')->default(1);
            $table->date('due_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('status', 30)->default('open')->index();
            $table->string('payment_method', 30)->nullable();
            $table->string('boleto_url')->nullable();
            $table->longText('pix_qr_code')->nullable();
            $table->longText('pix_copy_paste')->nullable();
            $table->string('external_reference')->nullable()->index();
            $table->dateTime('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_receivable_id')->constrained('accounts_receivable')->cascadeOnDelete();
            $table->foreignId('payment_installment_id')->nullable()->constrained('payment_installments')->nullOnDelete();
            $table->string('gateway', 30)->default('mercadopago')->index();
            $table->string('transaction_type', 30)->default('charge');
            $table->string('payment_method', 30)->nullable();
            $table->string('external_id')->nullable()->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->json('payload')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->dateTime('calculated_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->string('document', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('website')->nullable();
            $table->json('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('inventory_category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->string('unit_measure', 20)->default('un');
            $table->boolean('requires_batch')->default(false);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('batch_code')->nullable()->index();
            $table->decimal('quantity_received', 12, 3)->default(0);
            $table->decimal('quantity_available', 12, 3)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->date('expires_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->string('movement_type', 30)->index();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('before_stock', 12, 3)->default(0);
            $table->decimal('after_stock', 12, 3)->default(0);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->string('channel', 30)->default('whatsapp');
            $table->string('trigger_event', 50)->index();
            $table->string('subject')->nullable();
            $table->json('variables')->nullable();
            $table->longText('message');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maintenance_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->index();
            $table->string('value')->index();
            $table->dateTime('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('group');
            $table->string('key');
            $table->string('type', 30)->default('string');
            $table->boolean('is_public')->default(false);
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['unit_id', 'group', 'key']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index();
            $table->string('event_name', 100)->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('signature')->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->json('payload')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('pwa_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->cascadeOnDelete();
            $table->longText('endpoint')->unique();
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding', 50)->default('aes128gcm');
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_used_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_subscriptions');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('maintenance_whitelists');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_installments');
        Schema::dropIfExists('accounts_receivable');
        Schema::dropIfExists('document_acceptances');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('treatment_plan_items');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('odontogram_entries');
        Schema::dropIfExists('clinical_records');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('access_schedule_rules');
        Schema::dropIfExists('professionals');
        Schema::dropIfExists('patient_guardians');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('insurance_plans');
        Schema::dropIfExists('chairs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });
        Schema::dropIfExists('units');
    }
};
