<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Mappers;

use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Entities\CallEvent;
use App\Src\Connect\Domain\ValueObjects\CallStatus;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use App\Src\Connect\Infrastructure\Persistence\EloquentAgentState;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallEvent;
use DateTimeImmutable;

final class ConnectMapper
{
    public static function callEventToDomain(EloquentCallEvent $e): CallEvent
    {
        return new CallEvent(
            id: $e->id,
            externalCallId: $e->external_call_id,
            provider: new TelephonyProvider($e->provider ?? TelephonyProvider::CISCO_FINESSE),
            type: $e->type,
            status: new CallStatus($e->status ?? CallStatus::QUEUED),
            queueName: $e->queue_name,
            phoneNumber: $e->phone_number,
            citizenIdentifier: $e->citizen_identifier,
            employeeId: $e->employee_id,
            agentLoginId: $e->agent_login_id,
            startedAt: $e->started_at ? self::toImmutable($e->started_at) : null,
            endedAt: $e->ended_at ? self::toImmutable($e->ended_at) : null,
            talkTime: $e->talk_time,
            metadata: $e->metadata,
            occurredAt: self::toImmutable($e->created_at ?? 'now'),
        );
    }

    public static function callEventToEloquent(CallEvent $e): array
    {
        return [
            'external_call_id' => $e->externalCallId(),
            'provider' => $e->provider()->value(),
            'type' => $e->type(),
            'status' => $e->status()->value(),
            'queue_name' => $e->queueName(),
            'phone_number' => $e->phoneNumber(),
            'citizen_identifier' => $e->citizenIdentifier(),
            'employee_id' => $e->employeeId(),
            'agent_login_id' => $e->agentLoginId(),
            'started_at' => $e->startedAt()?->format('Y-m-d H:i:s'),
            'ended_at' => $e->endedAt()?->format('Y-m-d H:i:s'),
            'talk_time' => $e->talkTime(),
            'metadata' => $e->metadata(),
        ];
    }

    public static function agentStateToDomain(EloquentAgentState $e): AgentState
    {
        return new AgentState(
            id: $e->id,
            employeeId: $e->employee_id,
            externalId: $e->external_id ?? '',
            currentState: $e->current_state ?? 'UNKNOWN',
            reasonCode: $e->reason_code,
            lastChangedAt: self::toImmutable($e->last_changed_at ?? 'now'),
            provider: new TelephonyProvider(TelephonyProvider::CISCO_FINESSE),
            metadata: $e->metadata,
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
