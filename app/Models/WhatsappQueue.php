<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappQueue extends Model
{
    protected $table = 'whatsapp_queue';

    protected $fillable = [
        'phone', 'recipient_name', 'message', 'source', 'event', 'priority',
        'status', 'sender_id', 'scheduled_at', 'sent_at', 'retry_count',
        'max_retry', 'provider_response', 'error_message', 'unique_key',
        'created_by', 'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'status' => QueueStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'provider_response' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(WhatsappSender::class, 'sender_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappQueueLog::class, 'queue_id');
    }

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retry;
    }
}
