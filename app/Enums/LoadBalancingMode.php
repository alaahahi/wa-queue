<?php

namespace App\Enums;

enum LoadBalancingMode: string
{
    case LeastQueue = 'least_queue';
    case RoundRobin = 'round_robin';
    case Fixed = 'fixed';
    case Priority = 'priority';

    public function label(): string
    {
        return match ($this) {
            self::LeastQueue => 'Least Queue',
            self::RoundRobin => 'Round Robin',
            self::Fixed => 'Fixed Sender',
            self::Priority => 'Priority',
        };
    }
}
