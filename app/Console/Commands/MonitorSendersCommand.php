<?php

namespace App\Console\Commands;

use App\Services\Sender\SenderMonitorService;
use Illuminate\Console\Command;

class MonitorSendersCommand extends Command
{
    protected $signature = 'wa:monitor-senders';

    protected $description = 'Check TextMeBot connection status for all senders';

    public function handle(SenderMonitorService $monitor): int
    {
        $monitor->checkAll();
        $this->info('Sender status check completed.');

        return self::SUCCESS;
    }
}
