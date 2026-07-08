<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_sender_api_key_logs', function (Blueprint $table) {
            $table->string('key_hint', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_sender_api_key_logs', function (Blueprint $table) {
            $table->string('key_hint', 16)->change();
        });
    }
};
