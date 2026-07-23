<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebexService
{
    protected string $token;

    protected string $roomId;

    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.webex.bot_token', '');
        $this->roomId = config('services.webex.room_id', '');
        $this->apiUrl = 'https://webexapis.com/v1/messages';
    }

    public function sendText(string $message, ?string $roomId = null): ?array
    {
        return $this->send([
            'roomId' => $roomId ?? $this->roomId,
            'text' => $message,
        ]);
    }

    public function sendMarkdown(string $markdown, ?string $roomId = null): ?array
    {
        return $this->send([
            'roomId' => $roomId ?? $this->roomId,
            'markdown' => $markdown,
        ]);
    }

    public function sendToAll(string $message, ?string $roomId = null): ?array
    {
        return $this->sendMarkdown("**<@all>** {$message}", $roomId);
    }

    public function sendDirect(array $payload): ?array
    {
        return $this->send($payload);
    }

    private function send(array $payload): ?array
    {
        if (empty($this->token)) {
            Log::warning('WebexService: Token no configurado.');

            return null;
        }

        $hasRoom = ! empty($payload['roomId'] ?? $this->roomId);
        $hasPerson = ! empty($payload['toPersonEmail'] ?? $payload['toPersonId'] ?? null);

        if (! $hasRoom && ! $hasPerson) {
            Log::warning('WebexService: Sin destino — roomId ni toPersonEmail configurados.');

            return null;
        }

        if (! isset($payload['roomId']) && ! isset($payload['toPersonEmail']) && ! isset($payload['toPersonId'])) {
            $payload['roomId'] = $this->roomId;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WebexService: Error enviando mensaje', [
                'status' => $response->status(),
                'response' => $response->body(),
                'roomId' => $payload['roomId'] ?? null,
                'toPersonEmail' => $payload['toPersonEmail'] ?? null,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WebexService: Excepción al enviar mensaje', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
