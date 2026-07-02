<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CallCompleteDTO;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Services\CallLifecycleService;
use Illuminate\Support\Facades\Log;

final readonly class CompleteCallRecordHandler
{
    public function __construct(
        private CallLifecycleService $lifecycle,
    ) {}

    public function handle(CallCompleteDTO $dto): CallRecord
    {
        $record = $this->lifecycle->completeCall(
            $dto->callRecordId,
            $dto->talkTime,
            $dto->handleTime,
            $dto->contactDisposition,
        );

        Log::info('Call record completed.', [
            'record_id' => $dto->callRecordId,
            'talk_time' => $dto->talkTime,
        ]);

        return $record;
    }
}
