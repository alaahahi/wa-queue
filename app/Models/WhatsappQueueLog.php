<?php

namespace App\Models;

use App\Enums\QueueLogAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappQueueLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'queue_id', 'sender_id', 'action', 'message', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => QueueLogAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function queueMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsappQueue::class, 'queue_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(WhatsappSender::class, 'sender_id');
    }
}
