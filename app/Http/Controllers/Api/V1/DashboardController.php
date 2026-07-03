<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateSettingsRequest;
use App\Services\Analytics\AnalyticsService;
use App\Services\Sender\SenderMonitorService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly WhatsappQueueRepositoryInterface $queueRepository,
        private readonly SenderMonitorService $monitorService,
        private readonly AnalyticsService $analyticsService,
        private readonly WhatsappSettingsRepositoryInterface $settingsRepository,
    ) {}

    public function index(): array
    {
        return [
            'stats' => $this->queueRepository->getStats(),
            'senders' => $this->monitorService->getMonitorCards(),
            'settings' => $this->settingsRepository->all(),
        ];
    }

    public function analytics(): array
    {
        return $this->analyticsService->getDashboardAnalytics();
    }

    public function settings(): array
    {
        return $this->settingsRepository->all();
    }

    public function updateSettings(UpdateSettingsRequest $request): array
    {
        $this->settingsRepository->setMany($request->validated());

        return $this->settingsRepository->all();
    }
}
