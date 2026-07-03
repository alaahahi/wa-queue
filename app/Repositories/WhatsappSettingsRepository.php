<?php

namespace App\Repositories;

use App\Contracts\Repositories\WhatsappSettingsRepositoryInterface;
use App\Models\WhatsappSetting;

class WhatsappSettingsRepository implements WhatsappSettingsRepositoryInterface
{
    public function all(): array
    {
        $defaults = $this->getDefaults();
        $stored = WhatsappSetting::query()->pluck('value', 'key');

        $settings = [];
        foreach ($defaults as $key => $default) {
            $raw = $stored[$key] ?? null;
            $settings[$key] = $raw !== null
                ? (json_decode($raw, true) ?? $raw)
                : $default;
        }

        return $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return WhatsappSetting::getValue($key, $default ?? ($this->getDefaults()[$key] ?? null));
    }

    public function set(string $key, mixed $value): void
    {
        WhatsappSetting::setValue($key, $value);
    }

    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function getDefaults(): array
    {
        return [
            'queue_enabled' => true,
            'default_delay_seconds' => 6,
            'max_retry' => 3,
            'retry_delay_seconds' => 60,
            'load_balancing_mode' => 'least_queue',
            'automatic_failover' => true,
            'round_robin_enabled' => false,
            'offline_redistribute' => true,
            'fixed_sender_id' => null,
            'status_check_interval_seconds' => 60,
        ];
    }
}
