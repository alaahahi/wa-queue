<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSenderQueueJob;
use App\Models\WhatsappSender;
use Illuminate\Support\Facades\Artisan;

trait ProcessesTenantQueues
{
    protected function processTenantQueues(int $maxTime = 50): void
    {
        $queues = WhatsappSender::query()
            ->pluck('id')
            ->map(fn (int $id) => 'wa-sender-'.$id)
            ->push('default')
            ->unique()
            ->filter()
            ->implode(',');

        if ($queues === '') {
            return;
        }

        Artisan::call('queue:work', [
            '--queue' => $queues,
            '--stop-when-empty' => true,
            '--max-time' => $maxTime,
            '--tries' => 3,
            '--sleep' => 1,
        ]);
    }

    protected function ensureSenderJobsQueued(): void
    {
        foreach (WhatsappSender::query()->get() as $sender) {
            $hasAssigned = $sender->queueMessages()
                ->where('status', 'assigned')
                ->exists();

            if ($hasAssigned) {
                ProcessSenderQueueJob::dispatch($sender->id)
                    ->onQueue($sender->workerQueueName());
            }
        }
    }
}
