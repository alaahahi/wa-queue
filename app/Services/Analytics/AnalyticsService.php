<?php

namespace App\Services\Analytics;

use App\Enums\QueueStatus;
use App\Models\WhatsappQueue;
use App\Models\WhatsappSender;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardAnalytics(int $days = 7): array
    {
        $since = now()->subDays($days);

        return [
            'messages_per_sender' => $this->messagesPerSender($since),
            'success_rate_per_sender' => $this->successRatePerSender($since),
            'avg_sending_time' => $this->avgSendingTime($since),
            'messages_by_source' => $this->messagesBySource($since),
            'messages_by_hour' => $this->messagesByHour($since),
            'messages_by_day' => $this->messagesByDay($since),
            'top_active_senders' => $this->topActiveSenders($since),
        ];
    }

    private function messagesPerSender(\DateTimeInterface $since): array
    {
        return WhatsappQueue::query()
            ->select('sender_id', DB::raw('count(*) as total'))
            ->where('status', QueueStatus::Sent)
            ->where('sent_at', '>=', $since)
            ->groupBy('sender_id')
            ->with('sender:id,name')
            ->get()
            ->map(fn ($row) => [
                'sender' => $row->sender?->name ?? 'Unassigned',
                'total' => $row->total,
            ])
            ->toArray();
    }

    private function successRatePerSender(\DateTimeInterface $since): array
    {
        return WhatsappSender::query()
            ->get()
            ->map(function (WhatsappSender $sender) use ($since) {
                $total = WhatsappQueue::query()
                    ->where('sender_id', $sender->id)
                    ->where('updated_at', '>=', $since)
                    ->whereIn('status', [QueueStatus::Sent, QueueStatus::Failed])
                    ->count();

                $success = WhatsappQueue::query()
                    ->where('sender_id', $sender->id)
                    ->where('status', QueueStatus::Sent)
                    ->where('sent_at', '>=', $since)
                    ->count();

                return [
                    'sender' => $sender->name,
                    'rate' => $total > 0 ? round(($success / $total) * 100, 1) : 100,
                ];
            })
            ->toArray();
    }

    private function avgSendingTime(\DateTimeInterface $since): array
    {
        return WhatsappQueue::query()
            ->select('sender_id', DB::raw('AVG(duration_ms) as avg_ms'))
            ->where('status', QueueStatus::Sent)
            ->where('sent_at', '>=', $since)
            ->whereNotNull('duration_ms')
            ->groupBy('sender_id')
            ->with('sender:id,name')
            ->get()
            ->map(fn ($row) => [
                'sender' => $row->sender?->name ?? 'Unknown',
                'avg_ms' => round((float) $row->avg_ms),
            ])
            ->toArray();
    }

    private function messagesBySource(\DateTimeInterface $since): array
    {
        return WhatsappQueue::query()
            ->select('source', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $since)
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    private function messagesByHour(\DateTimeInterface $since): array
    {
        $hourExpr = $this->isSqlite()
            ? "strftime('%H', sent_at)"
            : 'HOUR(sent_at)';

        return WhatsappQueue::query()
            ->select(DB::raw("{$hourExpr} as hour"), DB::raw('count(*) as total'))
            ->where('status', QueueStatus::Sent)
            ->where('sent_at', '>=', $since)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    private function messagesByDay(\DateTimeInterface $since): array
    {
        $dayExpr = $this->isSqlite()
            ? "strftime('%Y-%m-%d', sent_at)"
            : 'DATE(sent_at)';

        return WhatsappQueue::query()
            ->select(DB::raw("{$dayExpr} as day"), DB::raw('count(*) as total'))
            ->where('status', QueueStatus::Sent)
            ->where('sent_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->toArray();
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function topActiveSenders(\DateTimeInterface $since): array
    {
        return WhatsappQueue::query()
            ->select('sender_id', DB::raw('count(*) as total'))
            ->where('status', QueueStatus::Sent)
            ->where('sent_at', '>=', $since)
            ->groupBy('sender_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('sender:id,name')
            ->get()
            ->map(fn ($row) => [
                'sender' => $row->sender?->name ?? 'Unknown',
                'total' => $row->total,
            ])
            ->toArray();
    }
}
