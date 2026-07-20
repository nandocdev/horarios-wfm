<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ScheduleException;
use Carbon\Carbon;

class SaveScheduleExceptionAction
{
    public function execute(int $employeeId, string $date, int $reasonId, bool $isFullDay, ?string $startTime, ?string $endTime, ?string $remarks, int $createdBy, ?int $exceptionId = null): ScheduleException
    {
        $startAt = Carbon::parse($date);
        $endAt = Carbon::parse($date);

        if ($isFullDay) {
            $startAt = $startAt->startOfDay();
            $endAt = $endAt->endOfDay();
        } else {
            $startAt = Carbon::parse($date.' '.($startTime ?? '08:00'));
            $endAt = Carbon::parse($date.' '.($endTime ?? '17:00'));
        }

        $data = [
            'employee_id' => $employeeId,
            'absence_reason_code_id' => $reasonId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'is_full_day' => $isFullDay,
            'remarks' => $remarks,
            'created_by' => $createdBy,
        ];

        if ($exceptionId) {
            $exception = ScheduleException::findOrFail($exceptionId);
            $exception->update($data);

            return $exception;
        }

        return ScheduleException::create($data);
    }
}
