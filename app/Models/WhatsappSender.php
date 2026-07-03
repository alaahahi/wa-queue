<?php

namespace App\Models;

use App\Enums\SenderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappSender extends Model
{
    protected $fillable = [
        'name', 'phone', 'api_key', 'status', 'delay_seconds', 'daily_limit',
        'today_sent', 'priority', 'last_sent_at', 'last_seen', 'last_error',
        'avg_response_ms', 'enabled', 'is_sending', 'round_robin_index',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'status' => SenderStatus::class,
            'last_sent_at' => 'datetime',
            'last_seen' => 'datetime',
            'enabled' => 'boolean',
            'is_sending' => 'boolean',
        ];
    }

    public function queueMessages(): HasMany
    {
        return $this->hasMany(WhatsappQueue::class, 'sender_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappQueueLog::class, 'sender_id');
    }

    public function pendingQueueCount(): int
    {
        return $this->queueMessages()
            ->whereIn('status', ['pending', 'assigned', 'sending'])
            ->count();
    }

    public function canSendNow(): bool
    {
        if (! $this->enabled || $this->status === SenderStatus::Offline) {
            return false;
        }

        if ($this->is_sending) {
            return false;
        }

        if ($this->today_sent >= $this->daily_limit) {
            return false;
        }

        if ($this->last_sent_at && $this->last_sent_at->addSeconds($this->delay_seconds)->isFuture()) {
            return false;
        }

        return true;
    }

    public function workerQueueName(): string
    {
        return 'wa-sender-'.$this->id;
    }
}
