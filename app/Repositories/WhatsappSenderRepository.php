<?php

namespace App\Repositories;

use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Enums\SenderStatus;
use App\Models\WhatsappQueue;
use App\Models\WhatsappSender;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WhatsappSenderRepository implements WhatsappSenderRepositoryInterface
{
    public function all(): Collection
    {
        return WhatsappSender::query()
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
        return WhatsappSender::query()->create($data);
    }

    public function update(WhatsappSender $sender, array $data): WhatsappSender
    {
        $sender->update($data);

        return $sender->fresh();
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
