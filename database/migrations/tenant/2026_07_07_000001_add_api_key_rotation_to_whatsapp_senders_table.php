<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_senders', function (Blueprint $table) {
            $table->timestamp('api_key_rotated_at')->nullable()->after('api_key');
        });

        Schema::create('whatsapp_sender_api_key_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('whatsapp_senders')->cascadeOnDelete();
            $table->string('key_hint', 16);
            $table->string('action', 20);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sender_id', 'created_at']);
        });

        \Illuminate\Support\Facades\DB::table('whatsapp_senders')
            ->whereNull('api_key_rotated_at')
            ->update(['api_key_rotated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sender_api_key_logs');

        Schema::table('whatsapp_senders', function (Blueprint $table) {
            $table->dropColumn('api_key_rotated_at');
        });
    }
};
