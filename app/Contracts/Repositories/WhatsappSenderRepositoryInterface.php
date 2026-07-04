<?php

namespace App\Contracts\Repositories;

use App\Models\WhatsappSender;
use Illuminate\Support\Collection;

interface WhatsappSenderRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?WhatsappSender;

    public function getEnabled(): Collection;

    public function getAvailableForDispatch(): Collection;

    public function create(array $data): WhatsappSender;

    public function update(WhatsappSender $sender, array $data): WhatsappSender;

    public function delete(WhatsappSender $sender): void;

    public function incrementTodaySent(WhatsappSender $sender): void;

    public function resetDailyCounters(): void;
}
