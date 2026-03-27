<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('provider', 30)->default('evolution')->after('channel');
            $table->time('delivery_window_start')->nullable()->after('subject');
            $table->time('delivery_window_end')->nullable()->after('delivery_window_start');
            $table->unsignedInteger('cooldown_seconds')->default(120)->after('delivery_window_end');
            $table->unsignedInteger('hourly_limit_per_recipient')->default(4)->after('cooldown_seconds');
            $table->boolean('requires_opt_in')->default(true)->after('hourly_limit_per_recipient');
            $table->boolean('requires_official_window')->default(true)->after('requires_opt_in');
            $table->json('meta')->nullable()->after('message');
        });

        Schema::create('whatsapp_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('recipient_phone', 30)->index();
            $table->string('trigger_event', 50)->nullable()->index();
            $table->string('provider', 30)->default('evolution');
            $table->string('status', 30)->default('blocked')->index();
            $table->string('external_id')->nullable()->index();
            $table->string('message_hash', 64)->nullable()->index();
            $table->dateTime('attempted_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('blocked_until')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_dispatch_logs');

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'delivery_window_start',
                'delivery_window_end',
                'cooldown_seconds',
                'hourly_limit_per_recipient',
                'requires_opt_in',
                'requires_official_window',
                'meta',
            ]);
        });
    }
};
