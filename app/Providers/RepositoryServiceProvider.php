<?php

namespace App\Providers;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Contracts\Repositories\WhatsappSenderRepositoryInterface;
use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Repositories\WhatsappQueueRepository;
use App\Repositories\WhatsappSenderRepository;
use App\Repositories\WhatsappSettingsRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WhatsappQueueRepositoryInterface::class, WhatsappQueueRepository::class);
        $this->app->bind(WhatsappSenderRepositoryInterface::class, WhatsappSenderRepository::class);
        $this->app->bind(WhatsappSettingsRepositoryInterface::class, WhatsappSettingsRepository::class);
    }
}
