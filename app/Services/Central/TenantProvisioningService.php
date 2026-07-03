<?php

namespace App\Services\Central;

use App\Models\Tenant;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

class TenantProvisioningService
{
    public function create(array $data): Tenant
    {
        $tenantId = $data['id'] ?? Str::slug($data['name']);

        if (Tenant::query()->where('id', $tenantId)->exists()) {
            throw new \InvalidArgumentException("Tenant ID [{$tenantId}] already exists.");
        }

        $tenant = Tenant::query()->create([
            'id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        if (! empty($data['domain'])) {
            if (Domain::query()->where('domain', $data['domain'])->exists()) {
                throw new \InvalidArgumentException("Domain [{$data['domain']}] is already taken.");
            }

            $tenant->domains()->create(['domain' => $data['domain']]);
        }

        return $tenant->fresh('domains');
    }

    public function addDomain(Tenant $tenant, string $domain): Domain
    {
        if (Domain::query()->where('domain', $domain)->exists()) {
            throw new \InvalidArgumentException("Domain [{$domain}] is already taken.");
        }

        return $tenant->domains()->create(['domain' => $domain]);
    }

    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }
}
