<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\System\WorkerHealthService;

class SystemStatusController extends Controller
{
    public function __invoke(WorkerHealthService $health): array
    {
        return $health->getStatus();
    }
}
