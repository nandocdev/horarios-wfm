<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallStartDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateCallRecordAction
{
    public function execute(CallStartDTO $dto): CallRecord
    {
        return DB::transaction(function () use ($dto) {
            $queueId = $this->resolveQueueId($dto->queueName);

            $record = CallRecord::firstOrCreate(
                ['cisco_call_id' => $dto->ciscoCallId],
                [
                    'queue_id' => $queueId,
                    'phone_number' => $dto->phoneNumber,
                    'ivr_started_at' => $dto->ivrStartedAt,
                    'status' => 'pending_operator',
                ]
            );

            return $record;
        });
    }

    private function resolveQueueId(string $queueName): ?int
    {
        if (empty($queueName)) {
            return null;
        }

        return CallQueue::where('name', $queueName)->value('id');
    }
}
