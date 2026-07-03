<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\EnqueueMessageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EnqueueMessageRequest;
use App\Http\Resources\WhatsappQueueResource;
use App\Services\Queue\DispatcherService;
use App\Services\Queue\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly DispatcherService $dispatcherService,
    ) {}

    public function store(EnqueueMessageRequest $request): JsonResponse
    {
        $message = $this->queueService->enqueue(
            EnqueueMessageData::fromArray($request->validated())
        );

        return (new WhatsappQueueResource($message))
            ->response()
            ->setStatusCode(201);
    }

    public function retry(int $id): WhatsappQueueResource
    {
        $message = $this->queueService->retry(
            app(\App\Contracts\Repositories\WhatsappQueueRepositoryInterface::class)->findById($id)
        );

        return new WhatsappQueueResource($message);
    }

    public function cancel(int $id): WhatsappQueueResource
    {
        $message = $this->queueService->cancel(
            app(\App\Contracts\Repositories\WhatsappQueueRepositoryInterface::class)->findById($id)
        );

        return new WhatsappQueueResource($message);
    }

    public function destroy(int $id): JsonResponse
    {
        $repo = app(\App\Contracts\Repositories\WhatsappQueueRepositoryInterface::class);
        $message = $repo->findById($id);

        if ($message) {
            $this->queueService->delete($message);
        }

        return response()->json(['message' => 'Deleted']);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate(['sender_id' => 'required|integer']);

        $repo = app(\App\Contracts\Repositories\WhatsappQueueRepositoryInterface::class);
        $message = $repo->findById($id);

        $success = $this->dispatcherService->assignManually($message, (int) $request->sender_id);

        return response()->json([
            'success' => $success,
            'data' => new WhatsappQueueResource($message->fresh(['sender'])),
        ]);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $request->validate(['sender_id' => 'required|integer']);

        $repo = app(\App\Contracts\Repositories\WhatsappQueueRepositoryInterface::class);
        $message = $repo->findById($id);

        $success = $this->dispatcherService->moveToSender($message, (int) $request->sender_id);

        return response()->json(['success' => $success]);
    }
}
