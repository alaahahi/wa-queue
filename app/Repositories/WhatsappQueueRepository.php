<?php

namespace App\Repositories;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\DTOs\EnqueueMessageData;
use App\Enums\QueueStatus;
use App\Models\WhatsappQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WhatsappQueueRepository implements WhatsappQueueRepositoryInterface
{
    public function enqueue(EnqueueMessageData $data, int $defaultMaxRetry): WhatsappQueue
    {
        return WhatsappQueue::query()->create([
            'phone' => $data->phone,
            'recipient_name' => $data->recipientName,
            'message' => $data->message,
            'source' => $data->source,
            'event' => $data->event,
            'priority' => $data->priority,
            'status' => QueueStatus::Pending,
            'scheduled_at' => $data->scheduledAt,
            'max_retry' => $data->maxRetry ?? $defaultMaxRetry,
            'unique_key' => $data->uniqueKey,
            'created_by' => $data->createdBy,
        ]);
    }

    public function findById(int $id): ?WhatsappQueue
    {
        return WhatsappQueue::query()->with('sender')->find($id);
    }

    public function findByUniqueKey(string $uniqueKey): ?WhatsappQueue
    {
        return WhatsappQueue::query()->where('unique_key', $uniqueKey)->first();
    }

    public function getPendingForDispatch(int $limit = 100): Collection
    {
        return WhatsappQueue::query()
            ->where('status', QueueStatus::Pending)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function getAssignedForSender(int $senderId): ?WhatsappQueue
    {
        return WhatsappQueue::query()
            ->where('sender_id', $senderId)
            ->where('status', QueueStatus::Assigned)
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->first();
    }

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = WhatsappQueue::query()->with('sender');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['phone'])) {
            $query->where('phone', 'like', '%'.$filters['phone'].'%');
        }
        if (! empty($filters['recipient'])) {
            $query->where('recipient_name', 'like', '%'.$filters['recipient'].'%');
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function getStats(): array
    {
        $today = now()->toDateString();

        $counts = WhatsappQueue::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $sentToday = WhatsappQueue::query()
            ->where('status', QueueStatus::Sent)
            ->whereDate('sent_at', $today)
            ->count();

        $avgDuration = WhatsappQueue::query()
            ->where('status', QueueStatus::Sent)
            ->whereDate('sent_at', $today)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');

        $totalProcessed = WhatsappQueue::query()
            ->whereIn('status', [QueueStatus::Sent, QueueStatus::Failed])
            ->whereDate('updated_at', $today)
            ->count();

        $successCount = WhatsappQueue::query()
            ->where('status', QueueStatus::Sent)
            ->whereDate('sent_at', $today)
            ->count();

        return [
            'pending' => (int) ($counts[QueueStatus::Pending->value] ?? 0),
            'assigned' => (int) ($counts[QueueStatus::Assigned->value] ?? 0),
            'sending' => (int) ($counts[QueueStatus::Sending->value] ?? 0),
            'sent_today' => $sentToday,
            'failed' => (int) ($counts[QueueStatus::Failed->value] ?? 0),
            'cancelled' => (int) ($counts[QueueStatus::Cancelled->value] ?? 0),
            'queue_size' => WhatsappQueue::query()
                ->whereIn('status', [QueueStatus::Pending, QueueStatus::Assigned, QueueStatus::Sending])
                ->count(),
            'avg_send_time_ms' => round((float) $avgDuration),
            'success_rate' => $totalProcessed > 0
                ? round(($successCount / $totalProcessed) * 100, 1)
                : 100,
        ];
    }

    public function updateStatus(WhatsappQueue $message, array $attributes): WhatsappQueue
    {
        $message->update($attributes);

        return $message->fresh(['sender']);
    }
}
