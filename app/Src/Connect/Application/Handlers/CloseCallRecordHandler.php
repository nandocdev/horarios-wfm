<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\CallCloseDTO;
use App\Src\Connect\Domain\Entities\CallRecord;
use App\Src\Connect\Domain\Services\CallLifecycleService;
use Illuminate\Support\Facades\Log;

final readonly class CloseCallRecordHandler
{
    public function __construct(
        private CallLifecycleService $lifecycle,
    ) {}

    public function handle(CallCloseDTO $dto): CallRecord
    {
        $record = $this->lifecycle->closeCall($dto->callRecordId);

        Log::info('Call record closed.', [
            'record_id' => $dto->callRecordId,
        ]);

        return $record;
    }
}
