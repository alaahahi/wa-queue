<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\WhatsappSender;
use App\Services\System\WorkerHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunQueueWorkerCommand extends Command
{
    protected $signature = 'wa:queue-work {--max-time=55 : Max seconds per cron run}';

    protected $description = 'Process WhatsApp jobs from all tenant sender queues (for cron)';

    public function handle(WorkerHealthService $health): int
    {
        $queues = $this->collectQueueNames();

        if ($queues === '') {
            $this->warn('No sender queues found.');

            return self::SUCCESS;
        }

        $this->info("Processing queues: {$queues}");

        Artisan::call('queue:work', [
            '--queue' => $queues,
            '--stop-when-empty' => true,
            '--max-time' => (int) $this->option('max-time'),
            '--tries' => 3,
            '--sleep' => 1,
        ]);

        $output = trim(Artisan::output());
        if ($output !== '') {
            $this->line($output);
        }

        foreach (Tenant::query()->cursor() as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $health->pingQueueWorker();
            } finally {
                tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function collectQueueNames(): string
    {
        $queues = collect(['default']);

        foreach (Tenant::query()->cursor() as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $queues = $queues->merge(
                    WhatsappSender::query()->pluck('id')->map(fn (int $id) => 'wa-sender-'.$id)
                );
            } finally {
                tenancy()->end();
            }
        }

        return $queues->unique()->filter()->implode(',');
    }
}
