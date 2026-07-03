<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_queue_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('whatsapp_queue')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('whatsapp_senders')->nullOnDelete();
            $table->string('action', 50);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('queue_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_queue_logs');
    }
};
