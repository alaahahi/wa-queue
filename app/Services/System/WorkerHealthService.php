<?php

namespace App\Services\System;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Enums\QueueStatus;
use App\Models\WhatsappQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorkerHealthService
{
    private const SCHEDULER_KEY = '_heartbeat_scheduler';

    private const QUEUE_WORKER_KEY = '_heartbeat_queue_worker';

    private const SCHEDULER_STALE_SECONDS = 180;

    private const QUEUE_WORKER_STALE_SECONDS = 180;

    public function __construct(
        private readonly WhatsappSettingsRepositoryInterface $settings,
    ) {}

    public function pingScheduler(): void
    {
        $this->settings->set(self::SCHEDULER_KEY, now()->toIso8601String());
    }

    public function pingQueueWorker(): void
    {
        $this->settings->set(self::QUEUE_WORKER_KEY, now()->toIso8601String());
    }

    public function getStatus(): array
    {
        $stats = app(WhatsappQueueRepositoryInterface::class)->getStats();
        $scheduler = $this->workerStatus(self::SCHEDULER_KEY, self::SCHEDULER_STALE_SECONDS);
        $senderWorker = $this->workerStatus(self::QUEUE_WORKER_KEY, self::QUEUE_WORKER_STALE_SECONDS);

        $lastSent = WhatsappQueue::query()
            ->where('status', QueueStatus::Sent)
            ->whereNotNull('sent_at')
            ->orderByDesc('sent_at')
            ->value('sent_at');

        $overall = $this->overallStatus($scheduler, $senderWorker, $stats);

        return [
            'scheduler' => $scheduler,
            'sender_worker' => $senderWorker,
            'overall' => $overall,
            'queue' => [
                'pending' => $stats['pending'],
                'assigned' => $stats['assigned'],
                'sending' => $stats['sending'],
                'failed' => $stats['failed'],
                'sent_today' => $stats['sent_today'],
                'queue_size' => $stats['queue_size'],
            ],
            'jobs_in_queue' => $this->centralJobsCount(),
            'last_sent_at' => $lastSent?->toIso8601String(),
            'last_sent_human' => $lastSent?->diffForHumans(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function centralJobsCount(): int
    {
        try {
            $connection = config('tenancy.database.central_connection', config('database.default'));

            return (int) DB::connection($connection)->table('jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function workerStatus(string $key, int $staleSeconds): array
    {
        $at = $this->parseTime($this->settings->get($key));

        if (! $at) {
            return [
                'alive' => false,
                'last_seen_at' => null,
                'last_seen_human' => null,
                'seconds_ago' => null,
                'label' => 'غير نشط',
            ];
        }

        $secondsAgo = (int) $at->diffInSeconds(now());
        $alive = $secondsAgo <= $staleSeconds;

        return [
            'alive' => $alive,
            'last_seen_at' => $at->toIso8601String(),
            'last_seen_human' => $at->diffForHumans(),
            'seconds_ago' => $secondsAgo,
            'label' => $alive ? 'يعمل' : 'متوقف',
        ];
    }

    private function overallStatus(array $scheduler, array $senderWorker, array $stats): array
    {
        $pending = (int) ($stats['queue_size'] ?? 0);

        if (! $scheduler['alive']) {
            return [
                'alive' => false,
                'label' => 'متوقف',
                'hint' => 'Cron غير نشط — أضف: * * * * * php artisan schedule:run',
            ];
        }

        if ($pending > 0 && ! $senderWorker['alive']) {
            return [
                'alive' => false,
                'label' => 'جزئي',
                'hint' => 'الجدولة تعمل لكن الإرسال متوقف — شغّل: php artisan wa:dispatch',
            ];
        }

        if (! $senderWorker['alive'] && $pending === 0) {
            return [
                'alive' => true,
                'label' => 'جاهز',
                'hint' => 'الجدولة تعمل — بانتظار رسائل جديدة',
            ];
        }

        return [
            'alive' => true,
            'label' => 'يعمل',
            'hint' => 'النظام يعالج الرسائل بشكل طبيعي',
        ];
    }

    private function parseTime(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
