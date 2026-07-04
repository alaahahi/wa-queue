<?php

namespace App\Console\Commands;

use App\Console\Concerns\RunsForAllTenants;
use App\Services\Sender\SenderMonitorService;
use Illuminate\Console\Command;

class MonitorSendersCommand extends Command
{
    use RunsForAllTenants;

    protected $signature = 'wa:monitor-senders';

    protected $description = 'Check TextMeBot connection status for all senders';

    public function handle(SenderMonitorService $monitor): int
    {
        $this->forEachTenant(function () use ($monitor) {
            $monitor->checkAll();
        });

        $this->info('Sender status check completed.');

        return self::SUCCESS;
    }
}
