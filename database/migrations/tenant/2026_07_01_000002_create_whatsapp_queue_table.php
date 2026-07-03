<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_queue', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('recipient_name')->nullable();
            $table->text('message');
            $table->string('source', 50);
            $table->string('event', 100)->nullable();
            $table->unsignedTinyInteger('priority')->default(5);
            $table->string('status', 20)->default('pending');
            $table->foreignId('sender_id')->nullable()->constrained('whatsapp_senders')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retry')->default(3);
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->string('unique_key')->nullable()->unique();
            $table->string('created_by')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['sender_id', 'status']);
            $table->index('source');
            $table->index('priority');
            $table->index('scheduled_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_queue');
    }
};
