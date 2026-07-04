<?php

namespace App\Console\Concerns;

use App\Models\Tenant;

trait RunsForAllTenants
{
    protected function forEachTenant(callable $callback): int
    {
        $total = 0;

        foreach (Tenant::query()->cursor() as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $total += (int) $callback($tenant);
            } finally {
                tenancy()->end();
            }
        }

        return $total;
    }
}
