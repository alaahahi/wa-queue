<?php

namespace App\Services\TextMeBot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextMeBotClient
{
    public function __construct(
        private readonly ?string $sendUrl = null,
        private readonly ?string $statusUrl = null,
    ) {}

    private function sendUrl(): string
    {
        return $this->sendUrl ?? config('whatsapp.send_url', 'https://api.textmebot.com/send.php');
    }

    private function statusUrl(): string
    {
        return $this->statusUrl ?? config('whatsapp.status_url', 'https://api.textmebot.com/status.php');
    }

    public function sendMessage(string $apiKey, string $phone, string $message): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(30)->get($this->sendUrl(), [
                'recipient' => $phone,
                'apikey' => $apiKey,
                'text' => $message,
                'json' => 'yes',
            ]);

            $durationMs = (int) ((microtime(true) - $start) * 1000);
            $body = $response->json() ?? ['raw' => $response->body()];

            return [
                'success' => $response->successful() && ($body['status'] ?? '') === 'success',
                'response' => $body,
                'duration_ms' => $durationMs,
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('TextMeBot send failed', ['error' => $e->getMessage(), 'phone' => $phone]);

            return [
                'success' => false,
                'response' => ['error' => $e->getMessage()],
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                'http_status' => 0,
            ];
        }
    }

    public function checkStatus(string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get($this->statusUrl(), [
                'apikey' => $apiKey,
            ]);

            $body = $response->json() ?? ['raw' => $response->body()];
            $connected = $response->successful()
                && (
                    ($body['status'] ?? '') === 'connected'
                    || ($body['connected'] ?? false) === true
                    || str_contains(strtolower($response->body()), 'connected')
                );

            return [
                'connected' => $connected,
                'response' => $body,
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'response' => ['error' => $e->getMessage()],
                'http_status' => 0,
            ];
        }
    }
}
