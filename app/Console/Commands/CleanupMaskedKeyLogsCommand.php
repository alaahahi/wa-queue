<?php

namespace App\Console\Commands;

use App\Console\Concerns\RunsForAllTenants;
use App\Models\WhatsappSenderApiKeyLog;
use Illuminate\Console\Command;

class CleanupMaskedKeyLogsCommand extends Command
{
    use RunsForAllTenants;

    protected $signature = 'wa:cleanup-key-logs';

    protected $description = 'Remove old masked (****) API key log entries that hold no recoverable value';

    public function handle(): int
    {
        $deleted = $this->forEachTenant(function ($tenant) {
            $count = WhatsappSenderApiKeyLog::query()
                ->where('key_hint', 'like', '****%')
                ->delete();

            $this->line("{$tenant->id}: removed {$count}");

            return $count;
        });

        $this->info("Done. Removed {$deleted} masked log entrie(s).");

        return self::SUCCESS;
    }
}
