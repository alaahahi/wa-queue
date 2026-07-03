<?php

namespace Database\Seeders;

use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Models\WhatsappSender;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(WhatsappSettingsRepositoryInterface::class)->setMany(
            app(WhatsappSettingsRepositoryInterface::class)->getDefaults()
        );

        if (WhatsappSender::query()->count() === 0) {
            WhatsappSender::query()->create([
                'name' => 'WhatsApp Business 1',
                'phone' => '+9647500000001',
                'api_key' => 'demo-api-key-replace-me',
                'delay_seconds' => 6,
                'daily_limit' => 500,
                'priority' => 10,
                'enabled' => false,
            ]);
        }
    }
}
