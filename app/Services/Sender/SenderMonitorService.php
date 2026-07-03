<?php

namespace App\Services\Sender;

use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Enums\SenderStatus;
use App\Models\WhatsappSender;
use App\Services\Queue\FailoverService;
use App\Services\TextMeBot\TextMeBotClient;

class SenderMonitorService
{
    public function __construct(
        private readonly WhatsappSenderRepositoryInterface $senderRepository,
        private readonly TextMeBotClient $textMeBotClient,
        private readonly FailoverService $failoverService,
    ) {}

    public function checkAll(): void
    {
        foreach ($this->senderRepository->getEnabled() as $sender) {
            $this->checkSender($sender);
        }
    }

    public function checkSender(WhatsappSender $sender): array
    {
        $result = $this->textMeBotClient->checkStatus($sender->api_key);
        $wasOnline = $sender->status !== SenderStatus::Offline;

        if ($result['connected']) {
            $status = $sender->is_sending ? SenderStatus::Busy : SenderStatus::Online;
            $sender->update([
                'status' => $status,
                'last_seen' => now(),
                'last_error' => null,
            ]);
        } else {
            $error = $result['response']['error'] ?? $result['response']['raw'] ?? 'Disconnected';
            $sender->update([
                'status' => SenderStatus::Offline,
                'last_seen' => now(),
                'last_error' => is_string($error) ? $error : json_encode($error),
            ]);

            if ($wasOnline) {
                $this->failoverService->handleSenderOffline($sender);
            }
        }

        return $result;
    }

    public function getMonitorCards(): array
    {
        return $this->senderRepository->all()->map(function (WhatsappSender $sender) {
            return [
                'id' => $sender->id,
                'name' => $sender->name,
                'phone' => $sender->phone,
                'status' => $sender->status->value,
                'status_label' => $sender->status->label(),
                'enabled' => $sender->enabled,
                'queue_count' => $sender->queue_count ?? $sender->pendingQueueCount(),
                'today_sent' => $sender->today_sent,
                'daily_limit' => $sender->daily_limit,
                'delay_seconds' => $sender->delay_seconds,
                'priority' => $sender->priority,
                'last_sent_at' => $sender->last_sent_at?->diffForHumans(),
                'last_seen' => $sender->last_seen?->diffForHumans(),
                'last_error' => $sender->last_error,
                'avg_response_ms' => $sender->avg_response_ms,
                'api_connected' => $sender->status !== SenderStatus::Offline,
            ];
        })->toArray();
    }
}
