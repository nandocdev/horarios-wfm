<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Services;

use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Events\CallRecordClosed;
use App\Src\Connect\Domain\Events\CallRecordCompleted;
use App\Src\Connect\Domain\Events\CallRecordStarted;
use App\Src\Connect\Domain\Repositories\CallRecordRepositoryInterface;
use DateTimeImmutable;

final class CallLifecycleService
{
    public function __construct(
        private CallRecordRepositoryInterface $repository,
    ) {}

    public function startCall(array $data): CallRecord
    {
        $record = new CallRecord(
            id: null,
            ciscoCallId: $data['cisco_call_id'] ?? null,
            queueId: $data['queue_id'] ?? null,
            phoneNumber: $data['phone_number'] ?? null,
            citizenIdentifier: $data['citizen_identifier'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            rawAgentName: $data['raw_agent_name'] ?? null,
            caseSubtypeId: $data['case_subtype_id'] ?? null,
            description: $data['description'] ?? null,
            status: 'pending_operator',
            talkTime: null,
            ringTime: null,
            workTime: null,
            queueTime: null,
            contactDisposition: null,
            ivrStartedAt: isset($data['ivr_started_at'])
                ? new DateTimeImmutable($data['ivr_started_at'])
                : new DateTimeImmutable(),
            ivrEndedAt: null,
            closedAt: null,
        );

        $saved = $this->repository->save($record);

        event(new CallRecordStarted($saved));

        return $saved;
    }

    public function closeCall(int $callRecordId): CallRecord
    {
        $record = $this->repository->findById($callRecordId);

        if ($record === null) {
            throw new \InvalidArgumentException("Call record {$callRecordId} not found.");
        }

        $saved = $this->repository->update($callRecordId, [
            'status' => 'closed',
            'closed_at' => new DateTimeImmutable(),
        ]);

        event(new CallRecordClosed($saved));

        return $saved;
    }

    public function completeCall(int $callRecordId, int $talkTime, int $handleTime, int $contactDisposition): CallRecord
    {
        $record = $this->repository->findById($callRecordId);

        if ($record === null) {
            throw new \InvalidArgumentException("Call record {$callRecordId} not found.");
        }

        $saved = $this->repository->update($callRecordId, [
            'status' => 'completed',
            'talk_time' => $talkTime,
            'work_time' => $handleTime,
            'contact_disposition' => $contactDisposition,
        ]);

        event(new CallRecordCompleted($saved));

        return $saved;
    }
}
