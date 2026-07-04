<?php

namespace App\Console\Commands;

use App\Console\Concerns\ProcessesTenantQueues;
use App\Console\Concerns\RunsForAllTenants;
use App\Models\Tenant;
use App\Models\WhatsappSender;
use App\Services\Queue\DispatcherService;
use App\Services\System\WorkerHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunDispatcherCommand extends Command
{
    use ProcessesTenantQueues;
    use RunsForAllTenants;

    protected $signature = 'wa:dispatch {--loop : Run continuously}';

    protected $description = 'Dispatch pending WhatsApp messages to available senders';

    public function handle(DispatcherService $dispatcher, WorkerHealthService $health): int
    {
        $run = function () use ($dispatcher, $health) {
            $assigned = $this->forEachTenant(function () use ($dispatcher, $health) {
                $health->pingScheduler();
                $count = $dispatcher->dispatch();
                $this->ensureSenderJobsQueued();

                return $count;
            });

            $this->processAllTenantQueues();

            foreach (Tenant::query()->cursor() as $tenant) {
                tenancy()->initialize($tenant);
                try {
                    $health->pingQueueWorker();
                } finally {
                    tenancy()->end();
                }
            }

            return $assigned;
        };

        if ($this->option('loop')) {
            $this->info('Dispatcher running in loop mode (Ctrl+C to stop)...');

            while (true) {
                $count = $run();
                if ($count > 0) {
                    $this->line('Assigned '.$count.' message(s) at '.now()->toDateTimeString());
                }
                sleep(5);
            }
        }

        $count = $run();
        $this->info("Assigned {$count} message(s).");

        return self::SUCCESS;
    }

    private function processAllTenantQueues(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

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

        $queueList = $queues->unique()->filter()->implode(',');

        if ($queueList === '') {
            return;
        }

        Artisan::call('queue:work', [
            '--queue' => $queueList,
            '--stop-when-empty' => true,
            '--max-time' => 50,
            '--tries' => 3,
            '--sleep' => 1,
        ]);
    }
}
