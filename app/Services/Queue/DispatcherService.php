<?php

namespace App\Services\Queue;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Enums\QueueLogAction;
use App\Enums\QueueStatus;
use App\Enums\SenderStatus;
use App\Jobs\ProcessSenderQueueJob;
use App\Models\WhatsappQueue;
use App\Models\WhatsappQueueLog;
use App\Models\WhatsappSender;

class DispatcherService
{
    public function __construct(
        private readonly WhatsappQueueRepositoryInterface $queueRepository,
        private readonly WhatsappSenderRepositoryInterface $senderRepository,
        private readonly WhatsappSettingsRepositoryInterface $settingsRepository,
        private readonly SenderSelectionService $selectionService,
    ) {}

    public function dispatch(int $batchSize = 50): int
    {
        if (! $this->settingsRepository->get('queue_enabled', true)) {
            return 0;
        }

        $assigned = 0;
        $pending = $this->queueRepository->getPendingForDispatch($batchSize);

        foreach ($pending as $message) {
            $sender = $this->selectionService->selectBestSender();

            if (! $sender) {
                break;
            }

            $this->assignMessage($message, $sender);
            ProcessSenderQueueJob::dispatch($sender->id)->onQueue($sender->workerQueueName());
            $assigned++;
        }

        return $assigned;
    }

    public function assignMessage(WhatsappQueue $message, WhatsappSender $sender): void
    {
        $this->queueRepository->updateStatus($message, [
            'sender_id' => $sender->id,
            'status' => QueueStatus::Assigned,
        ]);

        $this->log($message, QueueLogAction::Assigned, "Assigned to Sender {$sender->name}", $sender->id);
    }

    public function assignManually(WhatsappQueue $message, int $senderId): bool
    {
        $sender = $this->senderRepository->findById($senderId);

        if (! $sender || ! $sender->enabled) {
            return false;
        }

        $this->assignMessage($message, $sender);
        ProcessSenderQueueJob::dispatch($sender->id)->onQueue($sender->workerQueueName());

        return true;
    }

    public function moveToSender(WhatsappQueue $message, int $senderId): bool
    {
        if (! in_array($message->status, [QueueStatus::Pending, QueueStatus::Assigned, QueueStatus::Failed], true)) {
            return false;
        }

        $sender = $this->senderRepository->findById($senderId);

        if (! $sender || ! $sender->enabled) {
            return false;
        }

        $oldSenderId = $message->sender_id;

        $this->queueRepository->updateStatus($message, [
            'sender_id' => $sender->id,
            'status' => QueueStatus::Assigned,
            'error_message' => null,
        ]);

        $this->log($message, QueueLogAction::Moved, "Moved from Sender {$oldSenderId} to {$sender->name}", $sender->id, [
            'from_sender_id' => $oldSenderId,
        ]);

        ProcessSenderQueueJob::dispatch($sender->id)->onQueue($sender->workerQueueName());

        return true;
    }

    private function log(WhatsappQueue $message, QueueLogAction $action, string $text, ?int $senderId = null, array $meta = []): void
    {
        WhatsappQueueLog::query()->create([
            'queue_id' => $message->id,
            'sender_id' => $senderId,
            'action' => $action,
            'message' => $text,
            'metadata' => $meta,
            'created_at' => now(),
        ]);
    }
}
