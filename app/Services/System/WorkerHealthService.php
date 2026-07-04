<?php

namespace App\Services\System;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Enums\QueueStatus;
use App\Models\WhatsappQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WorkerHealthService
{
    private const SCHEDULER_KEY = 'wa:heartbeat:scheduler';

    private const QUEUE_WORKER_KEY = 'wa:heartbeat:queue_worker';

    private const SCHEDULER_STALE_SECONDS = 180;

    private const QUEUE_WORKER_STALE_SECONDS = 180;

    public function pingScheduler(): void
    {
        Cache::put(self::SCHEDULER_KEY, now()->toIso8601String(), 600);
    }

    public function pingQueueWorker(): void
    {
        Cache::put(self::QUEUE_WORKER_KEY, now()->toIso8601String(), 600);
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
            'last_sent_at' => $lastSent?->toIso8601String(),
            'last_sent_human' => $lastSent?->diffForHumans(),
        ];
    }

    private function workerStatus(string $key, int staleSeconds): array
    {
        $at = $this->parseTime(Cache::get($key));

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
                'hint' => 'فعّل Cron كل دقيقة: php artisan schedule:run',
            ];
        }

        if ($pending > 0 && ! $senderWorker['alive']) {
            return [
                'alive' => false,
                'label' => 'جزئي',
                'hint' => 'الجدولة تعمل لكن معالجة الإرسال متوقفة — استبدل Cron بـ: php artisan wa:queue-work',
            ];
        }

        if (! $senderWorker['alive'] && $pending === 0) {
            return [
                'alive' => true,
                'label' => 'جاهز',
                'hint' => 'الجدولة تعمل — معالجة الإرسال تُفحص كل دقيقة عبر wa:queue-work',
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
