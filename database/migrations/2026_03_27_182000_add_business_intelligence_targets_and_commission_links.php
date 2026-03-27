<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_entries', function (Blueprint $table) {
            $table->foreignId('account_receivable_id')
                ->nullable()
                ->after('professional_id')
                ->constrained('accounts_receivable')
                ->nullOnDelete();

            $table->index(['account_receivable_id', 'professional_id'], 'commission_account_professional_idx');
        });

        Schema::create('performance_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->string('metric', 50)->index();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->decimal('target_value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_targets');

        Schema::table('commission_entries', function (Blueprint $table) {
            $table->dropIndex('commission_account_professional_idx');
            $table->dropConstrainedForeignId('account_receivable_id');
        });
    }
};
