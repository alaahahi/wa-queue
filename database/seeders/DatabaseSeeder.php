<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::query()->where('id', 'demo')->exists()) {
            return;
        }

        $tenant = Tenant::query()->create([
            'id' => 'demo',
        ]);

        $tenant->domains()->create([
            'domain' => 'demo.wa-queue.test',
        ]);
    }
}
