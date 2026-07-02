<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\WebSockets;

use App\Src\Connect\Application\DTOs\IncomingCallEventDTO;
use App\Src\Connect\Application\Handlers\ProcessCallEventHandler;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use Illuminate\Support\Facades\Log;

final class TelephonyEventSubscriber
{
    private const CISCO_WEBHOOK_EVENTS = ['call_start', 'call_connected', 'call_completed', 'call_closed'];
    private const AVAYA_WEBHOOK_EVENTS = ['call_ringing', 'call_connected', 'call_held', 'call_retrieved', 'call_completed'];

    public function handleCiscoWebhook(array $payload): void
    {
        $event = $payload['event'] ?? '';

        if (! in_array($event, self::CISCO_WEBHOOK_EVENTS, true)) {
            Log::warning("Unknown Cisco event type: {$event}");
            return;
        }

        try {
            $handler = app(ProcessCallEventHandler::class);

            $handler->handle(new IncomingCallEventDTO(
                provider: TelephonyProvider::CISCO_FINESSE,
                payload: $payload,
            ));

        } catch (\Throwable $e) {
            Log::error("Failed to process Cisco webhook event '{$event}': {$e->getMessage()}");
        }
    }

    public function handleAvayaWebhook(array $payload): void
    {
        $event = $payload['event_type'] ?? '';

        if (! in_array($event, self::AVAYA_WEBHOOK_EVENTS, true)) {
            Log::warning("Unknown Avaya event type: {$event}");
            return;
        }

        try {
            $handler = app(ProcessCallEventHandler::class);

            $handler->handle(new IncomingCallEventDTO(
                provider: TelephonyProvider::AVAYA,
                payload: $payload,
            ));

        } catch (\Throwable $e) {
            Log::error("Failed to process Avaya webhook event '{$event}': {$e->getMessage()}");
        }
    }
}
