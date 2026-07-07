<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSenderRequest;
use App\Http\Requests\Api\V1\UpdateSenderRequest;
use App\Http\Resources\WhatsappSenderResource;
use App\Services\Queue\FailoverService;
use App\Services\Sender\SenderMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SenderController extends Controller
{
    public function __construct(
        private readonly WhatsappSenderRepositoryInterface $senderRepository,
        private readonly SenderMonitorService $monitorService,
        private readonly FailoverService $failoverService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return WhatsappSenderResource::collection($this->senderRepository->all());
    }

    public function store(StoreSenderRequest $request): WhatsappSenderResource
    {
        $sender = $this->senderRepository->create([
            ...$request->validated(),
            'delay_seconds' => $request->input('delay_seconds', 6),
            'daily_limit' => $request->input('daily_limit', 500),
            'priority' => $request->input('priority', 5),
            'enabled' => $request->input('enabled', true),
        ]);

        return new WhatsappSenderResource($sender);
    }

    public function update(UpdateSenderRequest $request, int $id): WhatsappSenderResource
    {
        $sender = $this->senderRepository->findById($id);
        $sender = $this->senderRepository->update($sender, $request->validated());

        return new WhatsappSenderResource($sender);
    }

    public function toggle(int $id): WhatsappSenderResource
    {
        $sender = $this->senderRepository->findById($id);
        $sender = $this->senderRepository->update($sender, ['enabled' => ! $sender->enabled]);

        return new WhatsappSenderResource($sender);
    }

    public function checkStatus(int $id): JsonResponse
    {
        $sender = $this->senderRepository->findById($id);
        $result = $this->monitorService->checkSender($sender);

        return response()->json([
            'sender' => new WhatsappSenderResource($sender->fresh()),
            'api' => $result,
        ]);
    }

    public function redistribute(int $id): JsonResponse
    {
        $sender = $this->senderRepository->findById($id);
        $count = $this->failoverService->redistributeFromSender($sender);

        return response()->json([
            'redistributed' => $count,
            'message' => "{$count} message(s) redistributed",
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $sender = $this->senderRepository->findById($id);
        $this->senderRepository->delete($sender);

        return response()->json(['message' => 'Sender deleted']);
    }

    public function monitor(): array
    {
        return ['senders' => $this->monitorService->getMonitorCards()];
    }
}
