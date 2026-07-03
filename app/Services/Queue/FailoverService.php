<?php

namespace App\Services\Queue;

use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Enums\QueueLogAction;
use App\Enums\QueueStatus;
use App\Enums\SenderStatus;
use App\Models\WhatsappQueue;
use App\Models\WhatsappQueueLog;
use App\Models\WhatsappSender;

class FailoverService
{
    public function __construct(
        private readonly WhatsappSettingsRepositoryInterface $settingsRepository,
    ) {}

    public function handleSenderOffline(WhatsappSender $sender): int
    {
        if (! $this->settingsRepository->get('automatic_failover', true)) {
            return 0;
        }

        $redistributed = 0;

        $messages = WhatsappQueue::query()
            ->where('sender_id', $sender->id)
            ->whereIn('status', [QueueStatus::Assigned, QueueStatus::Sending])
            ->get();

        foreach ($messages as $message) {
            $message->update([
                'status' => QueueStatus::Pending,
                'sender_id' => null,
            ]);

            WhatsappQueueLog::query()->create([
                'queue_id' => $message->id,
                'sender_id' => $sender->id,
                'action' => QueueLogAction::Failover,
                'message' => "Failover: redistributed from {$sender->name}",
                'created_at' => now(),
            ]);

            $redistributed++;
        }

        $sender->update([
            'status' => SenderStatus::Offline,
            'is_sending' => false,
            'enabled' => $this->settingsRepository->get('offline_redistribute', true)
                ? $sender->enabled
                : false,
        ]);

        return $redistributed;
    }

    public function redistributeFromSender(WhatsappSender $sender): int
    {
        return $this->handleSenderOffline($sender);
    }
}
