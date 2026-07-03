<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\QueueMonitorController;
use App\Http\Controllers\Api\V1\SenderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api'])->prefix('api/v1')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/analytics', [DashboardController::class, 'analytics']);
    Route::get('/settings', [DashboardController::class, 'settings']);
    Route::put('/settings', [DashboardController::class, 'updateSettings']);

    Route::get('/senders/monitor', [SenderController::class, 'monitor']);
    Route::apiResource('senders', SenderController::class)->only(['index', 'store', 'update']);
    Route::post('/senders/{id}/toggle', [SenderController::class, 'toggle']);
    Route::post('/senders/{id}/check-status', [SenderController::class, 'checkStatus']);
    Route::post('/senders/{id}/redistribute', [SenderController::class, 'redistribute']);

    Route::post('/queue', [QueueController::class, 'store']);
    Route::get('/queue', [QueueMonitorController::class, 'index']);
    Route::get('/queue/stats', [QueueMonitorController::class, 'stats']);
    Route::get('/queue/{id}/logs', [QueueMonitorController::class, 'logs']);
    Route::post('/queue/{id}/retry', [QueueController::class, 'retry']);
    Route::post('/queue/{id}/cancel', [QueueController::class, 'cancel']);
    Route::post('/queue/{id}/assign', [QueueController::class, 'assign']);
    Route::post('/queue/{id}/move', [QueueController::class, 'move']);
    Route::delete('/queue/{id}', [QueueController::class, 'destroy']);
});

Route::middleware(['web'])->group(function () {
    Route::get('/', fn () => view('app'));

    Route::get('/{any}', fn () => view('app'))
        ->where('any', '^(?!admin(?:/|$))(?!central(?:/|$)).+');
});
