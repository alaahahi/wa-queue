<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappQueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'recipient_name' => $this->recipient_name,
            'message' => $this->message,
            'source' => $this->source,
            'event' => $this->event,
            'priority' => $this->priority,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
            ]),
            'sender_id' => $this->sender_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'retry_count' => $this->retry_count,
            'max_retry' => $this->max_retry,
            'error_message' => $this->error_message,
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_by' => $this->created_by,
        ];
    }
}
