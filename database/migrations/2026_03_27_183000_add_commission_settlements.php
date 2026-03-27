<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->unsignedInteger('commission_count')->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('closed')->index();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('commission_entries', function (Blueprint $table) {
            $table->foreignId('commission_settlement_id')
                ->nullable()
                ->after('account_receivable_id')
                ->constrained('commission_settlements')
                ->nullOnDelete();

            $table->index(['commission_settlement_id', 'status'], 'commission_settlement_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('commission_entries', function (Blueprint $table) {
            $table->dropIndex('commission_settlement_status_idx');
            $table->dropConstrainedForeignId('commission_settlement_id');
        });

        Schema::dropIfExists('commission_settlements');
    }
};
