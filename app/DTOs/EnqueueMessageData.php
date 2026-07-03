<?php

namespace App\DTOs;

use App\Enums\MessageSource;

readonly class EnqueueMessageData
{
    public function __construct(
        public string $phone,
        public string $message,
        public string $source,
        public ?string $recipientName = null,
        public ?string $event = null,
        public int $priority = 5,
        public ?string $uniqueKey = null,
        public ?string $createdBy = null,
        public ?\DateTimeInterface $scheduledAt = null,
        public ?int $maxRetry = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'],
            message: $data['message'],
            source: $data['source'],
            recipientName: $data['recipient_name'] ?? null,
            event: $data['event'] ?? null,
            priority: (int) ($data['priority'] ?? 5),
            uniqueKey: $data['unique_key'] ?? null,
            createdBy: $data['created_by'] ?? null,
            scheduledAt: isset($data['scheduled_at']) ? new \DateTimeImmutable($data['scheduled_at']) : null,
            maxRetry: isset($data['max_retry']) ? (int) $data['max_retry'] : null,
        );
    }

    public function isValidSource(): bool
    {
        return in_array($this->source, MessageSource::values(), true);
    }
}
