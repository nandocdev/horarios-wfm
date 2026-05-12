<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallCompleteDTO;
use App\Modules\ConnectModule\Models\CallRecord;
use Illuminate\Support\Facades\DB;

final class CompleteCallRecordAction
{
    public function execute(CallRecord $record, CallCompleteDTO $dto): CallRecord
    {
        $identifier = $dto->citizenIdentifier ?: '0-000-000';

        if (! $this->validateCitizenIdentifier($identifier)) {
            throw new \InvalidArgumentException('Cédula inválida');
        }

        return DB::transaction(function () use ($record, $dto, $identifier) {
            $record->update([
                'employee_id' => $dto->employeeId,
                'citizen_identifier' => $identifier,
                'queue_id' => $dto->queueId,
                'case_subtype_id' => $dto->caseSubtypeId,
                'description' => $dto->description,
                'status' => $record->status === 'pending_operator' ? 'open' : $record->status,
            ]);

            return $record->refresh();
        });
    }

    private function validateCitizenIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Z0-9-]{6,15}$/', $identifier) === 1;
    }
}
