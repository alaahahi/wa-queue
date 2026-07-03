<?php

namespace App\Enums;

enum SenderStatus: string
{
    case Online = 'online';
    case Busy = 'busy';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Busy => 'Busy',
            self::Offline => 'Offline',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Online => 'online',
            self::Busy => 'busy',
            self::Offline => 'offline',
        };
    }
}
