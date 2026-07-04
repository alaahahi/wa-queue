<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSenderQueueJob;
use App\Models\Tenant;
use App\Models\WhatsappQueue;
use App\Models\WhatsappSender;
use App\Enums\QueueStatus;
use Illuminate\Console\Command;

class RetryStuckQueueCommand extends Command
{
    protected $signature = 'wa:retry-stuck';

    protected $description = 'Re-queue jobs for messages stuck in assigned status';

    public function handle(): int
    {
        $total = 0;

        foreach (Tenant::query()->cursor() as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $stuck = WhatsappQueue::query()
                    ->where('status', QueueStatus::Assigned)
                    ->get();

                foreach ($stuck as $message) {
                    if (! $message->sender_id) {
                        continue;
                    }

                    ProcessSenderQueueJob::dispatch($message->sender_id)
                        ->onQueue('wa-sender-'.$message->sender_id);

                    $total++;
                    $this->line("Tenant {$tenant->id}: re-queued message #{$message->id}");
                }

                WhatsappSender::query()
                    ->where('is_sending', true)
                    ->where('updated_at', '<', now()->subMinutes(2))
                    ->update(['is_sending' => false]);
            } finally {
                tenancy()->end();
            }
        }

        $this->info("Re-queued {$total} stuck message(s).");

        return self::SUCCESS;
    }
}
