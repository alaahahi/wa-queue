<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreTenantRequest;
use App\Http\Resources\Central\TenantResource;
use App\Models\Tenant;
use App\Services\Central\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantProvisioningService $provisioningService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return TenantResource::collection(
            Tenant::query()->with('domains')->orderBy('name')->get()
        );
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->provisioningService->create($request->validated());

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id): TenantResource
    {
        return new TenantResource(
            Tenant::query()->with('domains')->findOrFail($id)
        );
    }

    public function update(Request $request, string $id): TenantResource
    {
        $tenant = Tenant::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'in:active,suspended,trial'],
        ]);

        $tenant->update($validated);

        return new TenantResource($tenant->fresh('domains'));
    }

    public function destroy(string $id): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($id);
        $this->provisioningService->delete($tenant);

        return response()->json(['message' => 'Tenant deleted']);
    }

    public function addDomain(Request $request, string $id): JsonResponse
    {
        $request->validate(['domain' => ['required', 'string', 'max:255', 'unique:domains,domain']]);

        $tenant = Tenant::query()->findOrFail($id);
        $domain = $this->provisioningService->addDomain($tenant, $request->domain);

        return response()->json([
            'domain' => $domain->domain,
            'tenant' => new TenantResource($tenant->fresh('domains')),
        ], 201);
    }
}
