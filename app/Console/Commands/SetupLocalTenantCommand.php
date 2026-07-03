<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;

class SetupLocalTenantCommand extends Command
{
    protected $signature = 'wa:setup-local {--tenant=demo}';

    protected $description = 'Create demo tenant, migrate & seed';

    public function handle(): int
    {
        $centralDb = database_path('database.sqlite');
        if (! file_exists($centralDb)) {
            touch($centralDb);
            $this->call('migrate', ['--force' => true]);
            $this->info('Created central SQLite: database/database.sqlite');
        }

        $tenantId = $this->option('tenant');
        $appUrl = rtrim(config('app.url'), '/');

        $tenant = Tenant::query()->firstOrCreate(
            ['id' => $tenantId],
            ['name' => ucfirst($tenantId), 'status' => 'active']
        );

        $manager = $tenant->database()->manager();
        $database = $tenant->database()->getName();

        if (! $manager->databaseExists($database)) {
            $manager->createDatabase($tenant);
            $this->info("Created database: {$database}");
        }

        $this->call('tenants:migrate', ['--tenants' => [$tenantId], '--force' => true]);
        $this->call('tenants:seed', ['--tenants' => [$tenantId], '--force' => true]);

        $this->newLine();
        $this->info("Ready! Open: {$appUrl}/{$tenantId}");
        $this->line("API test: POST {$appUrl}/{$tenantId}/api/v1/queue");

        return self::SUCCESS;
    }
}
