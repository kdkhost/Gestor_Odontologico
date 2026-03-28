<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dateTime('privacy_last_exported_at')->nullable()->after('last_visit_at');
            $table->dateTime('anonymized_at')->nullable()->after('privacy_last_exported_at');
        });

        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_type', 30)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_channel', 30)->default('painel_admin');
            $table->string('legal_basis', 120)->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->string('export_path')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('payload')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status'], 'privacy_request_unit_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('privacy_requests', function (Blueprint $table) {
            $table->dropIndex('privacy_request_unit_status_idx');
        });

        Schema::dropIfExists('privacy_requests');

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_last_exported_at',
                'anonymized_at',
            ]);
        });
    }
};
