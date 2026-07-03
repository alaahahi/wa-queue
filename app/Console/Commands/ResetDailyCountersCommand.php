<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use Illuminate\Console\Command;

class ResetDailyCountersCommand extends Command
{
    protected $signature = 'wa:reset-daily-counters';

    protected $description = 'Reset today_sent counters on all WhatsApp senders';

    public function handle(WhatsappSenderRepositoryInterface $senderRepository): int
    {
        $senderRepository->resetDailyCounters();
        $this->info('Daily counters reset.');

        return self::SUCCESS;
    }
}
