<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsappQueueResource;
use App\Models\WhatsappQueueLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QueueMonitorController extends Controller
{
    public function __construct(
        private readonly WhatsappQueueRepositoryInterface $queueRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'status', 'source', 'sender_id', 'priority',
            'phone', 'recipient', 'date_from', 'date_to',
        ]);

        $messages = $this->queueRepository->paginate($filters, (int) $request->get('per_page', 25));

        return WhatsappQueueResource::collection($messages);
    }

    public function stats(): array
    {
        return $this->queueRepository->getStats();
    }

    public function logs(int $id): array
    {
        return WhatsappQueueLog::query()
            ->where('queue_id', $id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action->value,
                'message' => $log->message,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->toArray();
    }
}
