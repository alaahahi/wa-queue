<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\WhatsappSender;

class WhatsappSenderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'delay_seconds' => $this->delay_seconds,
            'daily_limit' => $this->daily_limit,
            'today_sent' => $this->today_sent,
            'priority' => $this->priority,
            'last_sent_at' => $this->last_sent_at?->toIso8601String(),
            'last_sent_human' => $this->last_sent_at?->diffForHumans(),
            'last_seen' => $this->last_seen?->toIso8601String(),
            'last_seen_human' => $this->last_seen?->diffForHumans(),
            'last_error' => $this->last_error,
            'avg_response_ms' => $this->avg_response_ms,
            'enabled' => $this->enabled,
            'is_sending' => $this->is_sending,
            'queue_count' => $this->queue_count ?? $this->pendingQueueCount(),
            'api_connected' => $this->status->value !== 'offline',
            'api_key' => $this->api_key,
            'api_key_hint' => WhatsappSender::apiKeyHint($this->api_key),
            'api_key_rotated_at' => $this->api_key_rotated_at?->toIso8601String(),
            'api_key_rotated_human' => $this->api_key_rotated_at?->diffForHumans(),
            'api_key_rotation_due' => $this->isApiKeyRotationDue(),
            'api_key_rotation_days' => (int) config('whatsapp.api_key_rotation_days', 7),
            'api_key_logs' => $this->whenLoaded('apiKeyLogs', fn () => $this->apiKeyLogs->map(fn ($log) => [
                'key_hint' => $log->key_hint,
                'action' => $log->action,
                'created_at' => $log->created_at?->toIso8601String(),
                'created_human' => $log->created_at?->diffForHumans(),
            ])),
        ];
    }
}
