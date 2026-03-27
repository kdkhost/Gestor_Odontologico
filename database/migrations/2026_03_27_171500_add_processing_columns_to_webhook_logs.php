<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('signature')->index();
            $table->unsignedInteger('attempts')->default(1)->after('status');
            $table->dateTime('first_received_at')->nullable()->after('attempts');
            $table->dateTime('last_received_at')->nullable()->after('first_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropColumn([
                'payload_hash',
                'attempts',
                'first_received_at',
                'last_received_at',
            ]);
        });
    }
};
