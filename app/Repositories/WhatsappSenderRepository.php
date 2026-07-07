<?php

namespace App\Repositories;

use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Enums\SenderStatus;
use App\Models\WhatsappQueue;
use App\Models\WhatsappSender;
use App\Models\WhatsappSenderApiKeyLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WhatsappSenderRepository implements WhatsappSenderRepositoryInterface
{
    public function all(): Collection
    {
        return WhatsappSender::query()
            ->with([
                'apiKeyLogs' => fn ($q) => $q->orderByDesc('created_at')->limit(10),
            ])
            ->withCount([
                'queueMessages as queue_count' => fn ($q) => $q->whereIn('status', ['pending', 'assigned', 'sending']),
            ])
            ->orderByDesc('priority')
            ->get();
    }

    public function findById(int $id): ?WhatsappSender
    {
        return WhatsappSender::query()->find($id);
    }

    public function getEnabled(): Collection
    {
        return WhatsappSender::query()->where('enabled', true)->get();
    }

    public function getAvailableForDispatch(): Collection
    {
        return WhatsappSender::query()
            ->where('enabled', true)
            ->where('status', '!=', SenderStatus::Offline)
            ->where('is_sending', false)
            ->whereColumn('today_sent', '<', 'daily_limit')
            ->withCount([
                'queueMessages as queue_count' => fn ($q) => $q->whereIn('status', ['pending', 'assigned', 'sending']),
            ])
            ->get()
            ->filter(fn (WhatsappSender $sender) => $sender->canSendNow());
    }

    public function create(array $data): WhatsappSender
    {
        $data['api_key_rotated_at'] = now();

        $sender = WhatsappSender::query()->create($data);

        $this->logApiKey($sender, WhatsappSender::apiKeyHint($sender->api_key), 'added');

        return $sender->fresh(['apiKeyLogs']);
    }

    public function update(WhatsappSender $sender, array $data): WhatsappSender
    {
        if (array_key_exists('api_key', $data) && $data['api_key'] !== $sender->api_key) {
            $this->logApiKey($sender, WhatsappSender::apiKeyHint($sender->api_key), 'rotated');
            $data['api_key_rotated_at'] = now();
        }

        $sender->update($data);

        return $sender->fresh(['apiKeyLogs']);
    }

    private function logApiKey(WhatsappSender $sender, string $keyHint, string $action): void
    {
        WhatsappSenderApiKeyLog::query()->create([
            'sender_id' => $sender->id,
            'key_hint' => $keyHint,
            'action' => $action,
            'created_at' => now(),
        ]);
    }

    public function delete(WhatsappSender $sender): void
    {
        $sender->delete();
    }

    public function incrementTodaySent(WhatsappSender $sender): void
    {
        $sender->increment('today_sent');
        $sender->update(['last_sent_at' => now()]);
    }

    public function resetDailyCounters(): void
    {
        WhatsappSender::query()->update(['today_sent' => 0]);
    }
}
