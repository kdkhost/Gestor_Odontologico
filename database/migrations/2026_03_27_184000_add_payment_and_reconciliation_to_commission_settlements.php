<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->after('status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('proof_path')->nullable()->after('payment_reference');
            $table->foreignId('paid_by_user_id')->nullable()->after('proof_path')->constrained('users')->nullOnDelete();
            $table->string('bank_statement_reference')->nullable()->after('paid_by_user_id');
            $table->dateTime('reconciled_at')->nullable()->after('paid_at');
            $table->foreignId('reconciled_by_user_id')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->text('reconciliation_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reconciled_by_user_id');
            $table->dropConstrainedForeignId('paid_by_user_id');
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'proof_path',
                'bank_statement_reference',
                'reconciled_at',
                'reconciliation_notes',
            ]);
        });
    }
};
