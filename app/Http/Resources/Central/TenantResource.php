<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customDomain = $this->whenLoaded('domains', fn () => $this->domains->first()?->domain);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'domains' => $this->whenLoaded('domains', fn () => $this->domains->pluck('domain')),
            'access_path' => $this->id,
            'dashboard_url' => url($this->id),
            'primary_domain' => $customDomain,
            'custom_domain_url' => $customDomain ? 'https://'.$customDomain : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
