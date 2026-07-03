<?php

namespace App\Enums;

enum MessageSource: string
{
    case Contracts = 'contracts';
    case Crm = 'crm';
    case Sales = 'sales';
    case Invoices = 'invoices';
    case Support = 'support';
    case Marketing = 'marketing';
    case Appointments = 'appointments';

    public function label(): string
    {
        return match ($this) {
            self::Contracts => 'Contracts',
            self::Crm => 'CRM',
            self::Sales => 'Sales',
            self::Invoices => 'Invoices',
            self::Support => 'Support',
            self::Marketing => 'Marketing',
            self::Appointments => 'Appointments',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
