<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Entities\CallEvent;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use DateTimeImmutable;

final class EloquentConnectRepository implements CallEventRepositoryInterface
{
    public function saveCallEvent(CallEvent $event): CallEvent
    {
        $eloquent = EloquentCallEvent::create(
            ConnectMapper::callEventToEloquent($event),
        );

        return ConnectMapper::callEventToDomain($eloquent);
    }

    public function findByExternalId(string $externalCallId, string $provider): ?CallEvent
    {
        $eloquent = EloquentCallEvent::where('external_call_id', $externalCallId)
            ->where('provider', $provider)
            ->first();

        return $eloquent ? ConnectMapper::callEventToDomain($eloquent) : null;
    }

    public function findOpenByEmployee(int $employeeId): array
    {
        return EloquentCallEvent::where('employee_id', $employeeId)
            ->whereIn('status', ['queued', 'ringing', 'connected', 'on_hold'])
            ->get()
            ->map(fn (EloquentCallEvent $e) => ConnectMapper::callEventToDomain($e))
            ->toArray();
    }

    public function findCallsByDate(DateTimeImmutable $date, ?int $queueId = null): array
    {
        $query = EloquentCallEvent::whereDate('started_at', $date->format('Y-m-d'));

        if ($queueId !== null) {
            $query->where('queue_id', $queueId);
        }

        return $query->get()
            ->map(fn (EloquentCallEvent $e) => ConnectMapper::callEventToDomain($e))
            ->toArray();
    }

    public function saveAgentState(AgentState $state): AgentState
    {
        $eloquent = EloquentAgentState::updateOrCreate(
            ['employee_id' => $state->employeeId()],
            [
                'external_id' => $state->externalId(),
                'current_state' => $state->currentState(),
                'reason_code' => $state->reasonCode(),
                'last_changed_at' => $state->lastChangedAt()->format('Y-m-d H:i:s'),
                'metadata' => $state->metadata(),
            ],
        );

        return ConnectMapper::agentStateToDomain($eloquent);
    }

    public function findAgentState(int $employeeId): ?AgentState
    {
        $eloquent = EloquentAgentState::where('employee_id', $employeeId)->first();
        return $eloquent ? ConnectMapper::agentStateToDomain($eloquent) : null;
    }
}
