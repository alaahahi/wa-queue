<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        ];
    }
}
