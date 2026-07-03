<?php

namespace App\Console\Commands;

use App\Services\Queue\DispatcherService;
use Illuminate\Console\Command;

class RunDispatcherCommand extends Command
{
    protected $signature = 'wa:dispatch {--loop : Run continuously}';

    protected $description = 'Dispatch pending WhatsApp messages to available senders';

    public function handle(DispatcherService $dispatcher): int
    {
        if ($this->option('loop')) {
            $this->info('Dispatcher running in loop mode (Ctrl+C to stop)...');

            while (true) {
                $count = $dispatcher->dispatch();
                if ($count > 0) {
                    $this->line('Assigned '.$count.' message(s) at '.now()->toDateTimeString());
                }
                sleep(5);
            }
        }

        $count = $dispatcher->dispatch();
        $this->info("Assigned {$count} message(s).");

        return self::SUCCESS;
    }
}
