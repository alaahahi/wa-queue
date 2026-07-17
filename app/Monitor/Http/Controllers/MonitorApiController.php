<?php

namespace App\Monitor\Http\Controllers;

use App\Monitor\Http\Controllers\Concerns\RespondsWithMonitorApi;
use App\Monitor\Services\DbStatusService;
use App\Monitor\Services\LaravelLogReader;
use App\Monitor\Services\LogReader;
use App\Monitor\Services\MetricsAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorApiController
{
    use RespondsWithMonitorApi;

    public function status(DbStatusService $dbStatus): JsonResponse
    {
        $snapshot = $dbStatus->snapshot(true);

        return $this->monitorJson([
            'status' => [
                'database' => $snapshot['database'] ?? null,
                'connection_id' => $snapshot['connection_id'] ?? null,
                'threads_connected' => $snapshot['threads_connected'] ?? null,
                'threads_running' => $snapshot['threads_running'] ?? null,
                'connections' => $snapshot['connections'] ?? null,
                'max_used_connections' => $snapshot['max_used_connections'] ?? null,
                'aborted_clients' => $snapshot['aborted_clients'] ?? null,
                'aborted_connects' => $snapshot['aborted_connects'] ?? null,
                'uptime' => $snapshot['uptime'] ?? null,
                'driver' => $snapshot['driver'] ?? null,
                'supported' => $snapshot['supported'] ?? null,
                'error' => $snapshot['error'] ?? null,
                'memory' => $dbStatus->formatMemory(),
                'peak_memory' => $dbStatus->formatMemory(memory_get_peak_usage(true)),
            ],
        ]);
    }

    public function metrics(Request $request, LogReader $reader, MetricsAggregator $aggregator): JsonResponse
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $records = $reader->readDailyLog($date);

        return $this->monitorJson([
            'date' => $date,
            'metrics' => $aggregator->aggregate($records),
        ]);
    }

    public function alerts(Request $request, LogReader $reader): JsonResponse
    {
        $limit = min((int) $request->query('limit', 100), 500);
        $alerts = array_slice(array_reverse($reader->readAlerts()), 0, $limit);

        return $this->monitorJson([
            'count' => count($alerts),
            'alerts' => $alerts,
        ]);
    }

    public function logs(Request $request, LogReader $reader): JsonResponse
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $type = $request->query('type');
        $limit = min((int) $request->query('limit', 500), 2000);

        $records = $reader->readDailyLog($date);

        if ($type) {
            $records = array_values(array_filter(
                $records,
                fn ($record) => ($record['type'] ?? '') === $type
            ));
        }

        $total = count($records);
        if ($total > $limit) {
            $records = array_slice($records, -$limit);
        }

        return $this->monitorJson([
            'date' => $date,
            'type' => $type,
            'total' => $total,
            'limit' => $limit,
            'records' => $records,
        ]);
    }

    public function dates(LogReader $reader): JsonResponse
    {
        $files = $reader->listDailyFiles();

        return $this->monitorJson([
            'dates' => array_map(
                fn ($file) => str_replace('.log', '', $file),
                $files
            ),
        ]);
    }

    public function overview(
        Request $request,
        DbStatusService $dbStatus,
        LogReader $reader,
        MetricsAggregator $aggregator,
        LaravelLogReader $laravelLogs
    ): JsonResponse {
        $date = $request->query('date', now()->format('Y-m-d'));
        $records = $reader->readDailyLog($date);
        $metrics = $aggregator->aggregate($records);
        $snapshot = $dbStatus->snapshot(true);
        $alertLimit = min((int) $request->query('alert_limit', 50), 200);
        $alerts = array_slice(array_reverse($reader->readAlerts()), 0, $alertLimit);

        $payload = [
            'date' => $date,
            'available_dates' => array_map(
                fn ($file) => str_replace('.log', '', $file),
                $reader->listDailyFiles()
            ),
            'status' => [
                'database' => $snapshot['database'] ?? null,
                'threads_connected' => $snapshot['threads_connected'] ?? null,
                'threads_running' => $snapshot['threads_running'] ?? null,
                'connections' => $snapshot['connections'] ?? null,
                'max_used_connections' => $snapshot['max_used_connections'] ?? null,
                'memory' => $dbStatus->formatMemory(),
                'uptime' => $snapshot['uptime'] ?? null,
                'driver' => $snapshot['driver'] ?? null,
                'supported' => $snapshot['supported'] ?? null,
                'error' => $snapshot['error'] ?? null,
            ],
            'metrics' => $metrics,
            'alerts' => $alerts,
        ];

        if (config('monitor.laravel_log.include_in_overview', true)) {
            $payload['laravel_logs'] = $laravelLogs->summary(
                (int) config('monitor.laravel_log.overview_limit', 30)
            );
        }

        return $this->monitorJson($payload);
    }

    public function laravelLogFiles(LaravelLogReader $reader): JsonResponse
    {
        return $this->monitorJson([
            'files' => $reader->listFiles(),
        ]);
    }

    public function laravelLogs(Request $request, LaravelLogReader $reader): JsonResponse
    {
        $result = $reader->readEntries([
            'file' => $request->query('file'),
            'level' => $request->query('level'),
            'search' => $request->query('search'),
            'limit' => $request->query('limit'),
        ]);

        return $this->monitorJson($result);
    }
}
