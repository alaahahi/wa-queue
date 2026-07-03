<?php

namespace App\Contracts\Repositories;

use App\DTOs\EnqueueMessageData;
use App\Models\WhatsappQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface WhatsappQueueRepositoryInterface
{
    public function enqueue(EnqueueMessageData $data, int $defaultMaxRetry): WhatsappQueue;

    public function findById(int $id): ?WhatsappQueue;

    public function findByUniqueKey(string $uniqueKey): ?WhatsappQueue;

    public function getPendingForDispatch(int $limit = 100): Collection;

    public function getAssignedForSender(int $senderId): ?WhatsappQueue;

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator;

    public function getStats(): array;

    public function updateStatus(WhatsappQueue $message, array $attributes): WhatsappQueue;
}
