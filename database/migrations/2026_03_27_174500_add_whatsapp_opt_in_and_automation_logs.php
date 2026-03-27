<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('whatsapp_opt_in')->default(false)->after('whatsapp');
            $table->dateTime('whatsapp_opt_in_at')->nullable()->after('whatsapp_opt_in');
        });

        Schema::create('automation_run_logs', function (Blueprint $table) {
            $table->id();
            $table->string('automation_type', 50)->index();
            $table->string('status', 30)->default('preview')->index();
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('payload')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_run_logs');

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_opt_in',
                'whatsapp_opt_in_at',
            ]);
        });
    }
};
