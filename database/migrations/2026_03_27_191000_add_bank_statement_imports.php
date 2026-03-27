<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('disk', 30)->default('local');
            $table->string('delimiter', 10)->default('auto');
            $table->boolean('has_header')->default(true);
            $table->string('status', 20)->default('processing');
            $table->unsignedInteger('total_lines')->default(0);
            $table->unsignedInteger('matched_suggestions_count')->default(0);
            $table->unsignedInteger('reconciled_lines_count')->default(0);
            $table->unsignedInteger('unmatched_lines_count')->default(0);
            $table->dateTime('imported_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained('bank_statement_imports')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->dateTime('transaction_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_absolute', 12, 2);
            $table->string('transaction_reference')->nullable();
            $table->foreignId('suggested_commission_settlement_id')->nullable()->constrained('commission_settlements')->nullOnDelete();
            $table->foreignId('matched_commission_settlement_id')->nullable()->constrained('commission_settlements')->nullOnDelete();
            $table->unsignedInteger('match_score')->nullable();
            $table->string('match_reason')->nullable();
            $table->dateTime('matched_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['bank_statement_import_id', 'matched_commission_settlement_id'], 'statement_import_match_idx');
            $table->index(['suggested_commission_settlement_id', 'match_score'], 'statement_suggestion_score_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropIndex('statement_import_match_idx');
            $table->dropIndex('statement_suggestion_score_idx');
        });

        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
    }
};
