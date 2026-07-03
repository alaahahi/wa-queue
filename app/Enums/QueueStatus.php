<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeSeverity(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Assigned => 'info',
            self::Sending => 'info',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'secondary',
        };
    }
}
