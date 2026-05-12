<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallCloseDTO;
use App\Modules\ConnectModule\Models\CallRecord;
use Illuminate\Support\Facades\DB;

final class CloseCallRecordAction
{
    public function execute(CallRecord $record, CallCloseDTO $dto): CallRecord
    {
        return DB::transaction(function () use ($record, $dto) {
            $record->update([
                'ivr_ended_at' => $dto->ivrEndedAt,
                'status' => $dto->status,
                'closed_at' => now(),
            ]);

            return $record->refresh();
        });
    }
}
