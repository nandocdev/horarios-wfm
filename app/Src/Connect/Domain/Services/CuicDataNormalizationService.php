<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Services;

use App\Src\Connect\Domain\Entities\AgentCallPerformance;
use App\Src\Connect\Domain\Entities\AgentStateTransition;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Entities\ChatRecord;
use DateTimeImmutable;

final class CuicDataNormalizationService
{
    public function normalizeInboundCall(array $raw): CallRecord
    {
        return new CallRecord(
            id: null,
            ciscoCallId: $raw['cisco_call_id'] ?? $raw['session_id'] ?? '',
            queueId: isset($raw['queue_id']) ? (int) $raw['queue_id'] : null,
            phoneNumber: $raw['phone_number'] ?? $raw['ani'] ?? null,
            citizenIdentifier: $raw['citizen_identifier'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            rawAgentName: $raw['raw_agent_name'] ?? null,
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
            closedAt: null,
        );
    }

    public function normalizeChatRecord(array $raw): ChatRecord
    {
        return new ChatRecord(
            id: null,
            conversationId: $raw['conversation_id'] ?? null,
            agentLoginId: $raw['agent_login_id'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            startTime: isset($raw['start_time']) ? new DateTimeImmutable($raw['start_time']) : null,
            endTime: isset($raw['end_time']) ? new DateTimeImmutable($raw['end_time']) : null,
            acceptedAt: isset($raw['accepted_at']) ? new DateTimeImmutable($raw['accepted_at']) : null,
            totalDuration: isset($raw['total_duration']) ? (int) $raw['total_duration'] : null,
            talkTime: isset($raw['talk_time']) ? (int) $raw['talk_time'] : null,
            authorIdentifier: $raw['author_identifier'] ?? null,
            destinationIdentifier: $raw['destination_identifier'] ?? null,
            chatType: $raw['chat_type'] ?? null,
            chatSource: $raw['chat_source'] ?? null,
            chatRating: isset($raw['chat_rating']) ? (int) $raw['chat_rating'] : null,
            rawAgentName: $raw['raw_agent_name'] ?? null,
        );
    }

    public function normalizeStateTransition(array $raw): AgentStateTransition
    {
        return new AgentStateTransition(
            id: null,
            agentLoginId: $raw['agent_login_id'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            transitionTime: isset($raw['transition_time']) ? new DateTimeImmutable($raw['transition_time']) : null,
            agentState: $raw['agent_state'] ?? null,
            reasonCode: $raw['reason_code'] ?? null,
            duration: isset($raw['duration']) ? (int) $raw['duration'] : null,
        );
    }

    public function normalizePerformance(array $raw): AgentCallPerformance
    {
        return new AgentCallPerformance(
            id: null,
            agentLoginId: $raw['agent_login_id'] ?? null,
            employeeId: isset($raw['employee_id']) ? (int) $raw['employee_id'] : null,
            agentExt: $raw['agent_ext'] ?? null,
            startTime: isset($raw['start_time']) ? new DateTimeImmutable($raw['start_time']) : null,
            endTime: isset($raw['end_time']) ? new DateTimeImmutable($raw['end_time']) : null,
            totalDuration: isset($raw['total_duration']) ? (int) $raw['total_duration'] : null,
            talkTime: isset($raw['talk_time']) ? (int) $raw['talk_time'] : null,
            holdTime: isset($raw['hold_time']) ? (int) $raw['hold_time'] : null,
            workTime: isset($raw['work_time']) ? (int) $raw['work_time'] : null,
            phoneNumber: $raw['phone_number'] ?? null,
            ani: $raw['ani'] ?? null,
            csqName: $raw['csq_name'] ?? null,
            callSkill: $raw['call_skill'] ?? null,
            callType: $raw['call_type'] ?? null,
            rawAgentName: $raw['raw_agent_name'] ?? null,
        );
    }
}
