<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\ManualCallRecordDTO;
use App\Modules\ConnectModule\Models\CallRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateManualCallRecordAction
{
    public function execute(ManualCallRecordDTO $dto): CallRecord
    {
        return DB::transaction(function () use ($dto) {
            return CallRecord::create([
                'cisco_call_id' => 'manual-'.Str::uuid(),
                'queue_id' => $dto->queueId,
                'phone_number' => $dto->phoneNumber,
                'ivr_started_at' => now(),
                'ivr_ended_at' => $dto->status === 'closed' ? now() : null,
                'employee_id' => $dto->employeeId,
                'citizen_identifier' => $dto->citizenIdentifier,
                'case_subtype_id' => $dto->caseSubtypeId,
                'description' => $dto->description,
                'status' => $dto->status,
                'closed_at' => $dto->status === 'closed' ? now() : null,
            ]);
        });
    }
}
