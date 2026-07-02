<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AvayaCallAdapter
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('connect.avaya.base_url', env('AVAYA_URL_BASE', ''));
        $this->apiKey = config('connect.avaya.api_key', env('AVAYA_API_KEY', ''));
        $this->timeout = (int) config('connect.avaya.timeout', 15);
    }

    public function getAgentState(string $agentId): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/agents/{$agentId}/state");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Avaya API error for agent {$agentId}: {$response->status()}");
            return null;

        } catch (\Throwable $e) {
            Log::error("Avaya connection error: {$e->getMessage()}");
            return null;
        }
    }

    public function getActiveCalls(): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/calls/active");

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return [];

        } catch (\Throwable $e) {
            Log::error("Avaya active calls error: {$e->getMessage()}");
            return [];
        }
    }
}
