<?php

namespace App\Services\Central;

use App\Contracts\Repositories\WhatsappQueueRepositoryInterface;
use App\Enums\SenderStatus;
use App\Models\Tenant;
use App\Models\WhatsappSender;
use App\Services\Sender\SenderMonitorService;
use App\Services\TextMeBot\TextMeBotClient;

class CrossTenantMonitorService
{
    public function __construct(
        private readonly TextMeBotClient $textMeBotClient,
    ) {}

    public function getOverview(bool $checkApi = false): array
    {
        $tenants = Tenant::query()->with('domains')->orderBy('name')->get();
        $overview = [];

        foreach ($tenants as $tenant) {
            $overview[] = $this->getTenantOverview($tenant, $checkApi);
        }

        return [
            'tenants' => $overview,
            'summary' => $this->buildSummary($overview),
        ];
    }

    public function getTenantOverview(Tenant $tenant, bool $checkApi = false): array
    {
        tenancy()->initialize($tenant);

        try {
            $senders = WhatsappSender::query()->orderBy('name')->get();
            $stats = app(WhatsappQueueRepositoryInterface::class)->getStats();

            $senderData = $senders->map(function (WhatsappSender $sender) use ($checkApi) {
                $apiConnected = $sender->status !== SenderStatus::Offline;

                if ($checkApi && $sender->enabled) {
                    $apiResult = $this->textMeBotClient->checkStatus($sender->api_key);
                    $apiConnected = $apiResult['connected'];

                    $sender->update([
                        'status' => $apiConnected
                            ? ($sender->is_sending ? SenderStatus::Busy : SenderStatus::Online)
                            : SenderStatus::Offline,
                        'last_seen' => now(),
                        'last_error' => $apiConnected
                            ? null
                            : ($apiResult['response']['error'] ?? 'Disconnected'),
                    ]);
                    $sender->refresh();
                }

                return [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'phone' => $sender->phone,
                    'status' => $sender->status->value,
                    'status_label' => $sender->status->label(),
                    'enabled' => $sender->enabled,
                    'api_connected' => $apiConnected,
                    'today_sent' => $sender->today_sent,
                    'daily_limit' => $sender->daily_limit,
                    'queue_count' => $sender->pendingQueueCount(),
                    'last_seen' => $sender->last_seen?->diffForHumans(),
                    'last_error' => $sender->last_error,
                    'avg_response_ms' => $sender->avg_response_ms,
                ];
            })->toArray();

            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'contact_phone' => $tenant->contact_phone,
                'status' => $tenant->status,
                'domains' => $tenant->domains->pluck('domain')->toArray(),
                'primary_domain' => $tenant->primaryDomain(),
                'dashboard_url' => $tenant->primaryDomain()
                    ? 'https://'.$tenant->primaryDomain()
                    : null,
                'senders' => $senderData,
                'senders_count' => count($senderData),
                'online_count' => collect($senderData)->where('status', 'online')->count(),
                'offline_count' => collect($senderData)->where('status', 'offline')->count(),
                'queue_stats' => $stats,
            ];
        } finally {
            tenancy()->end();
        }
    }

    public function checkTenantSenders(string $tenantId): array
    {
        $tenant = Tenant::query()->findOrFail($tenantId);

        return $this->getTenantOverview($tenant, checkApi: true);
    }

    public function checkAllSenders(): array
    {
        return $this->getOverview(checkApi: true);
    }

    private function buildSummary(array $overview): array
    {
        return [
            'total_tenants' => count($overview),
            'active_tenants' => collect($overview)->where('status', 'active')->count(),
            'total_senders' => collect($overview)->sum('senders_count'),
            'online_senders' => collect($overview)->sum('online_count'),
            'offline_senders' => collect($overview)->sum('offline_count'),
            'total_pending' => collect($overview)->sum(fn ($t) => $t['queue_stats']['pending'] ?? 0),
        ];
    }
}
