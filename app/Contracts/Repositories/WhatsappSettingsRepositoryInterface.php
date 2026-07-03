<?php

namespace App\Contracts\Repositories;

interface WhatsappSettingsRepositoryInterface
{
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function setMany(array $settings): void;

    public function getDefaults(): array;
}
