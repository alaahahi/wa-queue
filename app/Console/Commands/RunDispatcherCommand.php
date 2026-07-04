<?php

namespace App\Console\Commands;

use App\Console\Concerns\RunsForAllTenants;
use App\Services\Queue\DispatcherService;
use App\Services\System\WorkerHealthService;
use Illuminate\Console\Command;

class RunDispatcherCommand extends Command
{
    use RunsForAllTenants;

    protected $signature = 'wa:dispatch {--loop : Run continuously}';

    protected $description = 'Dispatch pending WhatsApp messages to available senders';

    public function handle(DispatcherService $dispatcher, WorkerHealthService $health): int
    {
        $run = fn () => $this->forEachTenant(function () use ($dispatcher, $health) {
            $health->pingScheduler();

            return $dispatcher->dispatch();
        });

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
}
