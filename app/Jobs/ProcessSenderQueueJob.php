<?php

namespace App\Jobs;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Enums\QueueLogAction;
use App\Enums\QueueStatus;
use App\Enums\SenderStatus;
use App\Models\WhatsappQueueLog;
use App\Models\WhatsappSender;
use App\Services\Queue\DispatcherService;
use App\Services\System\WorkerHealthService;
use App\Services\TextMeBot\TextMeBotClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessSenderQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $senderId) {}

    public function handle(
        WhatsappSenderRepositoryInterface $senderRepository,
        WhatsappQueueRepositoryInterface $queueRepository,
        WhatsappSettingsRepositoryInterface $settingsRepository,
        TextMeBotClient $textMeBotClient,
        DispatcherService $dispatcherService,
        WorkerHealthService $workerHealth,
    ): void {
        $workerHealth->pingSenderWorker();

        $sender = $senderRepository->findById($this->senderId);

        if (! $sender || ! $sender->enabled || ! $sender->canSendNow()) {
            return;
        }

        $message = $queueRepository->getAssignedForSender($sender->id);

        if (! $message) {
            return;
        }

        DB::transaction(function () use ($sender, $message, $senderRepository, $queueRepository, $textMeBotClient, $settingsRepository) {
            $sender->update(['is_sending' => true, 'status' => SenderStatus::Busy]);

            $queueRepository->updateStatus($message, ['status' => QueueStatus::Sending]);

            WhatsappQueueLog::query()->create([
                'queue_id' => $message->id,
                'sender_id' => $sender->id,
                'action' => QueueLogAction::StartedSending,
                'message' => 'Started sending',
                'created_at' => now(),
            ]);

            $result = $textMeBotClient->sendMessage($sender->api_key, $message->phone, $message->message);

            WhatsappQueueLog::query()->create([
                'queue_id' => $message->id,
                'sender_id' => $sender->id,
                'action' => QueueLogAction::ApiResponse,
                'message' => $result['success'] ? 'API success' : 'API failed',
                'metadata' => $result,
                'created_at' => now(),
            ]);

            $this->updateAvgResponse($sender, $result['duration_ms']);

            if ($result['success']) {
                $queueRepository->updateStatus($message, [
                    'status' => QueueStatus::Sent,
                    'sent_at' => now(),
                    'provider_response' => $result['response'],
                    'duration_ms' => $result['duration_ms'],
                    'error_message' => null,
                ]);

                $senderRepository->incrementTodaySent($sender);

                WhatsappQueueLog::query()->create([
                    'queue_id' => $message->id,
                    'sender_id' => $sender->id,
                    'action' => QueueLogAction::Completed,
                    'message' => 'Completed',
                    'created_at' => now(),
                ]);
            } else {
                $error = $result['response']['error'] ?? json_encode($result['response']);

                if ($message->canRetry()) {
                    $queueRepository->updateStatus($message, [
                        'status' => QueueStatus::Pending,
                        'sender_id' => null,
                        'retry_count' => $message->retry_count + 1,
                        'provider_response' => $result['response'],
                        'error_message' => is_string($error) ? $error : json_encode($error),
                    ]);

                    WhatsappQueueLog::query()->create([
                        'queue_id' => $message->id,
                        'sender_id' => $sender->id,
                        'action' => QueueLogAction::Retry,
                        'message' => 'Retry scheduled',
                        'created_at' => now(),
                    ]);
                } else {
                    $queueRepository->updateStatus($message, [
                        'status' => QueueStatus::Failed,
                        'provider_response' => $result['response'],
                        'error_message' => is_string($error) ? $error : json_encode($error),
                    ]);

                    WhatsappQueueLog::query()->create([
                        'queue_id' => $message->id,
                        'sender_id' => $sender->id,
                        'action' => QueueLogAction::Failed,
                        'message' => 'Failed permanently',
                        'created_at' => now(),
                    ]);
                }

                $sender->update(['last_error' => is_string($error) ? $error : json_encode($error)]);
            }

            $sender->update(['is_sending' => false, 'status' => SenderStatus::Online, 'last_seen' => now()]);
        });

        $delay = $sender->delay_seconds;
        self::dispatch($this->senderId)
            ->onQueue($sender->workerQueueName())
            ->delay(now()->addSeconds($delay));
    }

    private function updateAvgResponse(WhatsappSender $sender, int $durationMs): void
    {
        $avg = $sender->avg_response_ms > 0
            ? (int) (($sender->avg_response_ms + $durationMs) / 2)
            : $durationMs;

        $sender->update(['avg_response_ms' => $avg]);
    }
}
