<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_senders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20);
            $table->text('api_key');
            $table->string('status', 20)->default('offline');
            $table->unsignedInteger('delay_seconds')->default(6);
            $table->unsignedInteger('daily_limit')->default(500);
            $table->unsignedInteger('today_sent')->default(0);
            $table->unsignedTinyInteger('priority')->default(5);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('avg_response_ms')->default(0);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_sending')->default(false);
            $table->unsignedInteger('round_robin_index')->default(0);
            $table->timestamps();

            $table->index(['enabled', 'status']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_senders');
    }
};
