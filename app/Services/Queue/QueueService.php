<?php

namespace App\Services\Queue;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\DTOs\EnqueueMessageData;
use App\Enums\QueueLogAction;
use App\Enums\QueueStatus;
use App\Models\WhatsappQueue;
use App\Models\WhatsappQueueLog;
use Illuminate\Validation\ValidationException;

class QueueService
{
    public function __construct(
        private readonly WhatsappQueueRepositoryInterface $queueRepository,
        private readonly WhatsappSettingsRepositoryInterface $settingsRepository,
        private readonly DispatcherService $dispatcherService,
    ) {}

    public function enqueue(EnqueueMessageData $data): WhatsappQueue
    {
        if (! $data->isValidSource()) {
            throw ValidationException::withMessages(['source' => 'Invalid message source.']);
        }

        if ($data->uniqueKey) {
            $existing = $this->queueRepository->findByUniqueKey($data->uniqueKey);
            if ($existing) {
                return $existing;
            }
        }

        $maxRetry = $data->maxRetry ?? (int) $this->settingsRepository->get('max_retry', 3);
        $message = $this->queueRepository->enqueue($data, $maxRetry);

        WhatsappQueueLog::query()->create([
            'queue_id' => $message->id,
            'action' => QueueLogAction::Enqueued,
            'message' => "Enqueued from {$data->source}",
            'metadata' => ['event' => $data->event],
            'created_at' => now(),
        ]);

        if ($this->settingsRepository->get('queue_enabled', true)) {
            $this->dispatcherService->dispatch(1);
        }

        return $message->fresh(['sender']);
    }

    public function retry(WhatsappQueue $message): WhatsappQueue
    {
        if ($message->status !== QueueStatus::Failed && $message->status !== QueueStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => 'Only failed or cancelled messages can be retried.']);
        }

        $message = $this->queueRepository->updateStatus($message, [
            'status' => QueueStatus::Pending,
            'sender_id' => null,
            'error_message' => null,
            'retry_count' => $message->retry_count + 1,
        ]);

        WhatsappQueueLog::query()->create([
            'queue_id' => $message->id,
            'action' => QueueLogAction::Retry,
            'message' => "Retry #{$message->retry_count}",
            'created_at' => now(),
        ]);

        $this->dispatcherService->dispatch(1);

        return $message;
    }

    public function cancel(WhatsappQueue $message): WhatsappQueue
    {
        if (in_array($message->status, [QueueStatus::Sent, QueueStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['status' => 'Message cannot be cancelled.']);
        }

        $message = $this->queueRepository->updateStatus($message, [
            'status' => QueueStatus::Cancelled,
        ]);

        WhatsappQueueLog::query()->create([
            'queue_id' => $message->id,
            'sender_id' => $message->sender_id,
            'action' => QueueLogAction::Cancelled,
            'message' => 'Message cancelled',
            'created_at' => now(),
        ]);

        return $message;
    }

    public function delete(WhatsappQueue $message): void
    {
        $message->delete();
    }
}
