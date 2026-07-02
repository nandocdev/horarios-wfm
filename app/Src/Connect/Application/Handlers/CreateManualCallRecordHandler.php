<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\ManualCallRecordDTO;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Repositories\CallRecordRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

final readonly class CreateManualCallRecordHandler
{
    public function __construct(
        private CallRecordRepositoryInterface $repository,
    ) {}

    public function handle(ManualCallRecordDTO $dto): CallRecord
    {
        $record = new CallRecord(
            id: null,
            ciscoCallId: null,
            queueId: $dto->queueId,
            phoneNumber: $dto->phoneNumber,
            citizenIdentifier: $dto->citizenIdentifier,
            employeeId: $dto->employeeId,
            rawAgentName: null,
            caseSubtypeId: null,
            description: $dto->notes,
            status: 'pending_operator',
            talkTime: null,
            ringTime: null,
            workTime: null,
            queueTime: null,
            contactDisposition: null,
            ivrStartedAt: $dto->ivrStartedAt ? new DateTimeImmutable($dto->ivrStartedAt) : new DateTimeImmutable(),
            ivrEndedAt: null,
            closedAt: null,
        );

        $saved = $this->repository->save($record);

        Log::info('Manual call record created.', [
            'record_id' => $saved->id(),
            'queue_id' => $dto->queueId,
        ]);

        return $saved;
    }
}
