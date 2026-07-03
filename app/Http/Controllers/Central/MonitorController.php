<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Central\CrossTenantMonitorService;
use Illuminate\Http\JsonResponse;

class MonitorController extends Controller
{
    public function __construct(
        private readonly CrossTenantMonitorService $monitorService,
    ) {}

    public function index(): array
    {
        return $this->monitorService->getOverview(checkApi: false);
    }

    public function checkAll(): array
    {
        return $this->monitorService->checkAllSenders();
    }

    public function checkTenant(string $tenantId): array
    {
        return $this->monitorService->checkTenantSenders($tenantId);
    }
}
