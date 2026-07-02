<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\UccxCallDataDTO;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Events\CallRecordStarted;
use App\Src\Connect\Domain\Repositories\CallRecordRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class ImportUccxInboundHandler
{
    public function __construct(
        private CuicDataNormalizationService $normalizer,
        private CallRecordRepositoryInterface $repository,
    ) {}

    public function handle(UccxCallDataDTO $dto): CallRecord
    {
        $raw = $dto->rawData;
        $raw['cisco_call_id'] = $dto->getCiscoCallId();
        $raw['talk_time'] = $dto->getTalkTime();
        $raw['ring_time'] = $dto->getRingTime();
        $raw['work_time'] = $dto->getWorkTime();
        $raw['queue_time'] = $dto->getQueueTime();
        $raw['contact_disposition'] = $dto->getContactDisposition();
        $raw['phone_number'] = $dto->getOriginatingNumber();
        $raw['raw_agent_name'] = $dto->getAgentName();
        $raw['queue_name'] = $dto->getQueueName();
        $raw['ivr_started_at'] = $dto->getStartedAt();
        $raw['ivr_ended_at'] = $dto->getEndedAt();

        $existing = $this->repository->findByCiscoCallId($dto->getCiscoCallId());
        if ($existing !== null) {
            $merged = $this->mergeExisting($existing, $raw);
            $saved = $this->repository->save($merged);

            Log::info('UCCX inbound record merged.', [
                'call_id' => $dto->getCiscoCallId(),
                'record_id' => $saved->id(),
            ]);

            return $saved;
        }

        $record = $this->normalizer->normalizeInboundCall($raw);
        $saved = $this->repository->save($record);

        event(new CallRecordStarted($saved));

        Log::info('UCCX inbound record imported.', [
            'call_id' => $dto->getCiscoCallId(),
            'record_id' => $saved->id(),
        ]);

        return $saved;
    }

    private function mergeExisting(CallRecord $existing, array $raw): CallRecord
    {
        return new CallRecord(
            id: $existing->id(),
            ciscoCallId: $existing->ciscoCallId(),
            queueId: $existing->queueId() ?? ($raw['queue_id'] ?? null),
            phoneNumber: $existing->phoneNumber() ?? ($raw['phone_number'] ?? null),
            citizenIdentifier: $existing->citizenIdentifier(),
            employeeId: $existing->employeeId() ?? ($raw['employee_id'] ?? null),
            rawAgentName: $existing->rawAgentName() ?? ($raw['raw_agent_name'] ?? null),
            caseSubtypeId: $existing->caseSubtypeId(),
            description: $existing->description(),
            status: $this->resolveStatus((int) ($raw['contact_disposition'] ?? 0), $existing->status()),
            talkTime: max((int) ($existing->talkTime() ?? 0), (int) ($raw['talk_time'] ?? 0)),
            ringTime: max((int) ($existing->ringTime() ?? 0), (int) ($raw['ring_time'] ?? 0)),
            workTime: max((int) ($existing->workTime() ?? 0), (int) ($raw['work_time'] ?? 0)),
            queueTime: max((int) ($existing->queueTime() ?? 0), (int) ($raw['queue_time'] ?? 0)),
            contactDisposition: (int) ($raw['contact_disposition'] ?? $existing->contactDisposition()),
            ivrStartedAt: $existing->ivrStartedAt(),
            ivrEndedAt: $existing->ivrEndedAt(),
            closedAt: $existing->closedAt(),
        );
    }

    private function resolveStatus(int $disposition, ?string $currentStatus): string
    {
        return match (true) {
            $disposition === 1 => 'abandoned',
            $disposition === 2 => 'closed',
            $disposition === 4 => 'aborted',
            $disposition >= 5 && $disposition <= 98 => 'rejected',
            $disposition === 99 => 'cleansed',
            default => $currentStatus ?? 'pending_operator',
        };
    }
}
