<?php

namespace App\Models;

use App\Enums\SenderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappSender extends Model
{
    protected $fillable = [
        'name', 'phone', 'api_key', 'api_key_rotated_at', 'status', 'delay_seconds', 'daily_limit',
        'today_sent', 'priority', 'last_sent_at', 'last_seen', 'last_error',
        'avg_response_ms', 'enabled', 'is_sending', 'round_robin_index',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_key_rotated_at' => 'datetime',
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

    public function apiKeyLogs(): HasMany
    {
        return $this->hasMany(WhatsappSenderApiKeyLog::class, 'sender_id');
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

    public static function apiKeyHint(?string $apiKey): string
    {
        if (! $apiKey || strlen($apiKey) < 4) {
            return '****';
        }

        return '****'.substr($apiKey, -4);
    }

    public function isApiKeyRotationDue(): bool
    {
        $days = (int) config('whatsapp.api_key_rotation_days', 7);

        if (! $this->api_key_rotated_at) {
            return true;
        }

        return $this->api_key_rotated_at->lt(now()->subDays($days));
    }
}
