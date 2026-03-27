<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->string('file_type', 20)->default('csv')->after('original_name');
            $table->string('bank_profile', 30)->default('generic')->after('delimiter');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->dropColumn([
                'file_type',
                'bank_profile',
            ]);
        });
    }
};
