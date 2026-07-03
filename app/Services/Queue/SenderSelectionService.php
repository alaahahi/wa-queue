<?php

namespace App\Services\Queue;

use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Enums\LoadBalancingMode;
use App\Enums\SenderStatus;
use App\Models\WhatsappSender;
use Illuminate\Support\Collection;

class SenderSelectionService
{
    public function __construct(
        private readonly WhatsappSenderRepositoryInterface $senderRepository,
        private readonly WhatsappSettingsRepositoryInterface $settingsRepository,
    ) {}

    public function selectBestSender(): ?WhatsappSender
    {
        $candidates = $this->senderRepository->getAvailableForDispatch();

        if ($candidates->isEmpty()) {
            return null;
        }

        $mode = LoadBalancingMode::tryFrom(
            $this->settingsRepository->get('load_balancing_mode', 'least_queue')
        ) ?? LoadBalancingMode::LeastQueue;

        return match ($mode) {
            LoadBalancingMode::Fixed => $this->selectFixed($candidates),
            LoadBalancingMode::RoundRobin => $this->selectRoundRobin($candidates),
            LoadBalancingMode::Priority => $this->selectByPriority($candidates),
            default => $this->selectLeastQueue($candidates),
        };
    }

    private function selectFixed(Collection $candidates): ?WhatsappSender
    {
        $fixedId = $this->settingsRepository->get('fixed_sender_id');

        if (! $fixedId) {
            return $this->selectLeastQueue($candidates);
        }

        return $candidates->firstWhere('id', $fixedId)
            ?? $this->selectLeastQueue($candidates);
    }

    private function selectRoundRobin(Collection $candidates): ?WhatsappSender
    {
        $sorted = $candidates->sortBy('round_robin_index');

        $selected = $sorted->first();

        if ($selected) {
            WhatsappSender::query()
                ->where('id', $selected->id)
                ->update(['round_robin_index' => now()->timestamp]);
        }

        return $selected;
    }

    private function selectByPriority(Collection $candidates): ?WhatsappSender
    {
        return $candidates
            ->sortByDesc('priority')
            ->sortBy('queue_count')
            ->first();
    }

    private function selectLeastQueue(Collection $candidates): ?WhatsappSender
    {
        return $candidates
            ->sortBy('queue_count')
            ->sortByDesc('priority')
            ->first();
    }
}
