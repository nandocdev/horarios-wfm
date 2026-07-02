<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CallStartDTO;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Services\CallLifecycleService;
use Illuminate\Support\Facades\Log;

final readonly class CreateCallRecordHandler
{
    public function __construct(
        private CallLifecycleService $lifecycle,
    ) {}

    public function handle(CallStartDTO $dto): CallRecord
    {
        $record = $this->lifecycle->startCall([
            'queue_id' => $dto->queueId,
            'employee_id' => $dto->employeeId,
            'phone_number' => $dto->phoneNumber,
            'citizen_identifier' => $dto->citizenIdentifier,
            'ivr_started_at' => $dto->ivrStartedAt,
        ]);

        Log::info('Call record created.', [
            'record_id' => $record->id(),
            'queue_id' => $dto->queueId,
        ]);

        return $record;
    }
}
