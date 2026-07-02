<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Mappers;

use App\Src\Connect\Domain\Entities\AgentCallPerformance;
use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Entities\AgentStateTransition;
use App\Src\Connect\Domain\Entities\CallEvent;
use App\Src\Connect\Domain\Entities\CallQueue;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Entities\CaseSubtype;
use App\Src\Connect\Domain\Entities\Channel;
use App\Src\Connect\Domain\Entities\ChatRecord;
use App\Src\Connect\Domain\Entities\CsqRealtimeStat;
use App\Src\Connect\Domain\ValueObjects\CallStatus;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use App\Src\Connect\Infrastructure\Persistence\EloquentAgentCallPerformance;
use App\Src\Connect\Infrastructure\Persistence\EloquentAgentState;
use App\Src\Connect\Infrastructure\Persistence\EloquentAgentStateTransition;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallEvent;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallQueue;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use App\Src\Connect\Infrastructure\Persistence\EloquentCaseSubtype;
use App\Src\Connect\Infrastructure\Persistence\EloquentChannel;
use App\Src\Connect\Infrastructure\Persistence\EloquentChatRecord;
use App\Src\Connect\Infrastructure\Persistence\EloquentCsqRealtimeStat;
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

    public static function agentStateTransitionToDomain(EloquentAgentStateTransition $e): AgentStateTransition
    {
        return new AgentStateTransition(
            id: $e->id,
            agentLoginId: $e->agent_login_id,
            employeeId: $e->employee_id,
            transitionTime: $e->transition_time ? self::toImmutable($e->transition_time) : null,
            agentState: $e->agent_state,
            reasonCode: $e->reason_code,
            duration: $e->duration,
        );
    }

    public static function agentStateTransitionToEloquent(AgentStateTransition $t): array
    {
        return [
            'agent_login_id' => $t->agentLoginId(),
            'employee_id' => $t->employeeId(),
            'transition_time' => $t->transitionTime()?->format('Y-m-d H:i:s'),
            'agent_state' => $t->agentState(),
            'reason_code' => $t->reasonCode(),
            'duration' => $t->duration(),
        ];
    }

    public static function callRecordToDomain(EloquentCallRecord $e): CallRecord
    {
        return new CallRecord(
            id: $e->id,
            ciscoCallId: $e->cisco_call_id,
            queueId: $e->queue_id,
            phoneNumber: $e->phone_number,
            citizenIdentifier: $e->citizen_identifier,
            employeeId: $e->employee_id,
            rawAgentName: $e->raw_agent_name,
            caseSubtypeId: $e->case_subtype_id,
            description: $e->description,
            status: $e->status,
            talkTime: $e->talk_time,
            ringTime: $e->ring_time,
            workTime: $e->work_time,
            queueTime: $e->queue_time,
            contactDisposition: $e->contact_disposition,
            ivrStartedAt: $e->ivr_started_at ? self::toImmutable($e->ivr_started_at) : null,
            ivrEndedAt: $e->ivr_ended_at ? self::toImmutable($e->ivr_ended_at) : null,
            closedAt: $e->closed_at ? self::toImmutable($e->closed_at) : null,
        );
    }

    public static function callRecordToEloquent(CallRecord $r): array
    {
        return [
            'cisco_call_id' => $r->ciscoCallId(),
            'queue_id' => $r->queueId(),
            'phone_number' => $r->phoneNumber(),
            'citizen_identifier' => $r->citizenIdentifier(),
            'employee_id' => $r->employeeId(),
            'raw_agent_name' => $r->rawAgentName(),
            'case_subtype_id' => $r->caseSubtypeId(),
            'description' => $r->description(),
            'status' => $r->status(),
            'talk_time' => $r->talkTime(),
            'ring_time' => $r->ringTime(),
            'work_time' => $r->workTime(),
            'queue_time' => $r->queueTime(),
            'contact_disposition' => $r->contactDisposition(),
            'ivr_started_at' => $r->ivrStartedAt()?->format('Y-m-d H:i:s'),
            'ivr_ended_at' => $r->ivrEndedAt()?->format('Y-m-d H:i:s'),
            'closed_at' => $r->closedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    public static function callQueueToDomain(EloquentCallQueue $e): CallQueue
    {
        return new CallQueue(
            id: $e->id,
            name: $e->name,
            description: $e->description,
            extension: $e->extension ?? null,
            isActive: (bool) $e->is_active,
        );
    }

    public static function callQueueToEloquent(CallQueue $q): array
    {
        return [
            'name' => $q->name(),
            'description' => $q->description(),
            'extension' => $q->extension(),
            'is_active' => $q->isActive(),
        ];
    }

    public static function channelToDomain(EloquentChannel $e): Channel
    {
        return new Channel(
            id: $e->id,
            name: $e->name,
            type: $e->type ?? 'voice',
            isActive: (bool) $e->is_active,
        );
    }

    public static function channelToEloquent(Channel $c): array
    {
        return [
            'id' => $c->id(),
            'name' => $c->name(),
            'type' => $c->type(),
            'is_active' => $c->isActive(),
        ];
    }

    public static function caseSubtypeToDomain(EloquentCaseSubtype $e): CaseSubtype
    {
        return new CaseSubtype(
            id: $e->id,
            name: $e->name,
            description: $e->description,
            isActive: (bool) $e->is_active,
        );
    }

    public static function caseSubtypeToEloquent(CaseSubtype $s): array
    {
        return [
            'name' => $s->name(),
            'description' => $s->description(),
            'is_active' => $s->isActive(),
        ];
    }

    public static function chatRecordToDomain(EloquentChatRecord $e): ChatRecord
    {
        return new ChatRecord(
            id: $e->id,
            conversationId: $e->conversation_id,
            agentLoginId: $e->agent_login_id,
            employeeId: $e->employee_id,
            startTime: $e->start_time ? self::toImmutable($e->start_time) : null,
            endTime: $e->end_time ? self::toImmutable($e->end_time) : null,
            acceptedAt: $e->accepted_at ? self::toImmutable($e->accepted_at) : null,
            totalDuration: $e->total_duration,
            talkTime: $e->talk_time,
            authorIdentifier: $e->author_identifier,
            destinationIdentifier: $e->destination_identifier,
            chatType: $e->chat_type,
            chatSource: $e->chat_source,
            chatRating: $e->chat_rating,
            rawAgentName: $e->raw_agent_name,
        );
    }

    public static function chatRecordToEloquent(ChatRecord $c): array
    {
        return [
            'conversation_id' => $c->conversationId(),
            'agent_login_id' => $c->agentLoginId(),
            'employee_id' => $c->employeeId(),
            'start_time' => $c->startTime()?->format('Y-m-d H:i:s'),
            'end_time' => $c->endTime()?->format('Y-m-d H:i:s'),
            'accepted_at' => $c->acceptedAt()?->format('Y-m-d H:i:s'),
            'total_duration' => $c->totalDuration(),
            'talk_time' => $c->talkTime(),
            'author_identifier' => $c->authorIdentifier(),
            'destination_identifier' => $c->destinationIdentifier(),
            'chat_type' => $c->chatType(),
            'chat_source' => $c->chatSource(),
            'chat_rating' => $c->chatRating(),
            'raw_agent_name' => $c->rawAgentName(),
        ];
    }

    public static function agentCallPerformanceToDomain(EloquentAgentCallPerformance $e): AgentCallPerformance
    {
        return new AgentCallPerformance(
            id: $e->id,
            agentLoginId: $e->agent_login_id,
            employeeId: $e->employee_id,
            agentExt: $e->agent_ext,
            startTime: $e->start_time ? self::toImmutable($e->start_time) : null,
            endTime: $e->end_time ? self::toImmutable($e->end_time) : null,
            totalDuration: $e->total_duration,
            talkTime: $e->talk_time,
            holdTime: $e->hold_time,
            workTime: $e->work_time,
            phoneNumber: $e->phone_number,
            ani: $e->ani,
            csqName: $e->csq_name,
            callSkill: $e->call_skill,
            callType: $e->call_type,
            rawAgentName: $e->raw_agent_name,
        );
    }

    public static function agentCallPerformanceToEloquent(AgentCallPerformance $p): array
    {
        return [
            'agent_login_id' => $p->agentLoginId(),
            'employee_id' => $p->employeeId(),
            'agent_ext' => $p->agentExt(),
            'start_time' => $p->startTime()?->format('Y-m-d H:i:s'),
            'end_time' => $p->endTime()?->format('Y-m-d H:i:s'),
            'total_duration' => $p->totalDuration(),
            'talk_time' => $p->talkTime(),
            'hold_time' => $p->holdTime(),
            'work_time' => $p->workTime(),
            'phone_number' => $p->phoneNumber(),
            'ani' => $p->ani(),
            'csq_name' => $p->csqName(),
            'call_skill' => $p->callSkill(),
            'call_type' => $p->callType(),
            'raw_agent_name' => $p->rawAgentName(),
        ];
    }

    public static function csqRealtimeStatToDomain(EloquentCsqRealtimeStat $e): CsqRealtimeStat
    {
        return new CsqRealtimeStat(
            id: $e->id,
            csqName: $e->csq_name,
            callsWaiting: $e->calls_waiting,
            longestCallInQueue: $e->longest_call_in_queue,
            agentsLoggedOn: $e->agents_logged_on,
            agentsTalking: $e->agents_talking,
            agentsReady: $e->agents_ready,
            agentsNotReady: $e->agents_not_ready,
            agentsAfterCallWork: $e->agents_after_call_work,
            agentsReserved: $e->agents_reserved,
            serviceLevelShortTerm: $e->service_level_short_term,
            serviceLevelLongTerm: $e->service_level_long_term,
            callsAbandonedSinceMidnight: $e->calls_abandoned_since_midnight,
            callsHandledSinceMidnight: $e->calls_handled_since_midnight,
            totalCallsSinceMidnight: $e->total_calls_since_midnight,
            metadata: $e->metadata,
            createdAt: self::toImmutable($e->created_at ?? 'now'),
        );
    }

    public static function csqRealtimeStatToEloquent(CsqRealtimeStat $s): array
    {
        return [
            'csq_name' => $s->csqName(),
            'calls_waiting' => $s->callsWaiting(),
            'longest_call_in_queue' => $s->longestCallInQueue(),
            'agents_logged_on' => $s->agentsLoggedOn(),
            'agents_talking' => $s->agentsTalking(),
            'agents_ready' => $s->agentsReady(),
            'agents_not_ready' => $s->agentsNotReady(),
            'agents_after_call_work' => $s->agentsAfterCallWork(),
            'agents_reserved' => $s->agentsReserved(),
            'service_level_short_term' => $s->serviceLevelShortTerm(),
            'service_level_long_term' => $s->serviceLevelLongTerm(),
            'calls_abandoned_since_midnight' => $s->callsAbandonedSinceMidnight(),
            'calls_handled_since_midnight' => $s->callsHandledSinceMidnight(),
            'total_calls_since_midnight' => $s->totalCallsSinceMidnight(),
            'metadata' => $s->metadata(),
        ];
    }

    public static function agentStateTransitionFromRaw(array $raw): AgentStateTransition
    {
        return new AgentStateTransition(
            id: null,
            agentLoginId: $raw['agent_login_id'] ?? $raw['loginId'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            transitionTime: isset($raw['transition_time']) ? new DateTimeImmutable($raw['transition_time']) : null,
            agentState: $raw['agent_state'] ?? $raw['state'] ?? null,
            reasonCode: $raw['reason_code'] ?? null,
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
        );
    }

    public static function callRecordFromRaw(array $raw): CallRecord
    {
        return new CallRecord(
            id: null,
            ciscoCallId: $raw['cisco_call_id'] ?? $raw['session_id'] ?? null,
            queueId: isset($raw['queue_id']) ? (int) $raw['queue_id'] : null,
            phoneNumber: $raw['phone_number'] ?? $raw['ani'] ?? null,
            citizenIdentifier: $raw['citizen_identifier'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            rawAgentName: $raw['raw_agent_name'] ?? $raw['agent_name'] ?? null,
            caseSubtypeId: isset($raw['case_subtype_id']) ? (int) $raw['case_subtype_id'] : null,
            description: $raw['description'] ?? null,
            status: $raw['status'] ?? 'pending_operator',
            talkTime: isset($raw['talk_time']) ? (int) $raw['talk_time'] : null,
            ringTime: isset($raw['ring_time']) ? (int) $raw['ring_time'] : null,
            workTime: isset($raw['work_time']) ? (int) $raw['work_time'] : null,
            queueTime: isset($raw['queue_time']) ? (int) $raw['queue_time'] : null,
            contactDisposition: isset($raw['contact_disposition']) ? (int) $raw['contact_disposition'] : null,
            ivrStartedAt: isset($raw['ivr_started_at']) ? new DateTimeImmutable($raw['ivr_started_at']) : null,
            ivrEndedAt: isset($raw['ivr_ended_at']) ? new DateTimeImmutable($raw['ivr_ended_at']) : null,
            closedAt: isset($raw['closed_at']) ? new DateTimeImmutable($raw['closed_at']) : null,
        );
    }

    public static function agentPerformanceFromRaw(array $raw): AgentCallPerformance
    {
        return new AgentCallPerformance(
            id: null,
            agentLoginId: $raw['agent_login_id'] ?? $raw['loginId'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            agentExt: $raw['agent_ext'] ?? $raw['extension'] ?? null,
            startTime: isset($raw['start_time']) ? new DateTimeImmutable($raw['start_time']) : null,
            endTime: isset($raw['end_time']) ? new DateTimeImmutable($raw['end_time']) : null,
            totalDuration: isset($raw['total_duration']) ? (int) $raw['total_duration'] : null,
            talkTime: isset($raw['talk_time']) ? (int) $raw['talk_time'] : null,
            holdTime: isset($raw['hold_time']) ? (int) $raw['hold_time'] : null,
            workTime: isset($raw['work_time']) ? (int) $raw['work_time'] : null,
            phoneNumber: $raw['phone_number'] ?? null,
            ani: $raw['ani'] ?? null,
            csqName: $raw['csq_name'] ?? $raw['csqName'] ?? null,
            callSkill: $raw['call_skill'] ?? null,
            callType: $raw['call_type'] ?? null,
            rawAgentName: $raw['raw_agent_name'] ?? $raw['agent_name'] ?? null,
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
