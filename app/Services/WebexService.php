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

    private function send(array $payload): ?array
    {
        if (empty($this->token)) {
            Log::warning('WebexService: Token no configurado.');

            return null;
        }

        $targetRoom = $payload['roomId'] ?? $this->roomId;

        if (empty($targetRoom)) {
            Log::warning('WebexService: Room ID no configurado.');

            return null;
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
                'roomId' => $targetRoom,
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
