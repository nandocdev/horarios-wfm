<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Services;

use App\Src\Connect\Domain\Entities\CallEvent;
use App\Src\Connect\Domain\ValueObjects\CallStatus;
use DateTimeImmutable;

final class TelephonyNormalizationService {
    public function normalizeCiscoWebhook(array $payload): CallEvent {
        $type = $payload['event'] ?? 'call_start';
        $externalCallId = $payload['call_id'] ?? $payload['ciscoCallId'] ?? '';

        $status = match ($type) {
            'call_start' => CallStatus::queued(),
            'call_connected' => CallStatus::connected(),
            'call_completed' => CallStatus::completed(),
            'call_closed' => CallStatus::closed(),
            default => CallStatus::queued(),
        };

        $startedAt = isset($payload['timestamp'])
            ? new DateTimeImmutable($payload['timestamp'])
            : (isset($payload['ivr_started_at']) ? new DateTimeImmutable($payload['ivr_started_at']) : null);

        $endedAt = isset($payload['end_timestamp'])
            ? new DateTimeImmutable($payload['end_timestamp'])
            : (isset($payload['ivr_ended_at']) ? new DateTimeImmutable($payload['ivr_ended_at']) : null);

        return CallEvent::fromCiscoWebhook(
            externalCallId: $externalCallId,
            type: $type,
            status: $status,
            queueName: $payload['queue_name'] ?? null,
            phoneNumber: $payload['ani'] ?? $payload['phone_number'] ?? null,
            agentLoginId: $payload['agent_login_id'] ?? $payload['username'] ?? null,
            startedAt: $startedAt,
            endedAt: $endedAt,
            talkTime: isset($payload['talk_time']) ? (int) $payload['talk_time'] : null,
            metadata: $payload,
        );
    }

    public function normalizeAvayaWebhook(array $payload): CallEvent {
        $type = $payload['event_type'] ?? 'call_start';
        $externalCallId = $payload['call_id'] ?? $payload['session_id'] ?? '';

        $status = match ($payload['state'] ?? '') {
            'ringing' => CallStatus::ringing(),
            'connected' => CallStatus::connected(),
            'completed' => CallStatus::completed(),
            default => CallStatus::queued(),
        };

        return CallEvent::fromAvayaWebhook(
            externalCallId: $externalCallId,
            type: $type,
            status: $status,
            queueName: $payload['queue'] ?? null,
            phoneNumber: $payload['caller_id'] ?? null,
            agentLoginId: $payload['agent_id'] ?? null,
        );
    }
}
