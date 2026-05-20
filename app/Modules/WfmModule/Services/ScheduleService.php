<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Services;

use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\DTOs\Schedules\ScheduleDayDTO;
use Carbon\CarbonInterface;

final class ScheduleService implements ScheduleServiceInterface
{
    public function getScheduleForEmployee(int $employeeId, CarbonInterface $date): ScheduleDayDTO
    {
        $batch = $this->getBatchSchedules([$employeeId], $date);

        return $batch[$employeeId] ?? new ScheduleDayDTO($employeeId, $date->toDateString(), null, null, is_off: true);
    }

    public function getBatchSchedules(array $employeeIds, CarbonInterface $date): array
    {
        if (empty($employeeIds)) {
            return [];
        }

        $dayOfWeek = $date->dayOfWeekIso;
        $weekStart = $date->copy()->startOfWeek();

        $weeklySchedule = WeeklySchedule::where('week_start_date', $weekStart->format('Y-m-d'))->first();

        $assignments = [];
        if ($weeklySchedule) {
            $assignments = WeeklyScheduleAssignment::with(['schedule'])
                ->where('weekly_schedule_id', $weeklySchedule->id)
                ->whereIn('employee_id', $employeeIds)
                ->where('day_of_week', $dayOfWeek)
                ->get()
                ->keyBy('employee_id');
        }

        $exceptions = ScheduleException::with(['reason'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('start_at', '<=', $date->toDateString())
            ->whereDate('end_at', '>=', $date->toDateString())
            ->get()
            ->groupBy('employee_id');

        $results = [];
        foreach ($employeeIds as $id) {
            $assignment = $assignments[$id] ?? null;
            $empExceptions = $exceptions[$id] ?? collect();

            $results[$id] = new ScheduleDayDTO(
                employee_id: $id,
                date: $date->toDateString(),
                start_time: $assignment?->start_time?->format('H:i:s'),
                end_time: $assignment?->end_time?->format('H:i:s'),
                lunch_start_time: $assignment?->lunch_start_time?->format('H:i:s'),
                lunch_end_time: $assignment?->lunch_end_time?->format('H:i:s'),
                break_start_time: $assignment?->break_start_time?->format('H:i:s'),
                break_end_time: $assignment?->break_end_time?->format('H:i:s'),
                lunch_minutes: (int) ($assignment?->schedule?->lunch_minutes ?? 45),
                break_minutes: (int) ($assignment?->schedule?->break_minutes ?? 15),
                is_off: $assignment === null,
                exceptions: $empExceptions->map(fn ($e) => [
                    'type' => $e->reason?->name ?? 'Exception',
                    'color' => $e->reason?->color_hex ?? '#ef4444',
                    'start_at' => $e->start_at?->toIso8601String(),
                    'end_at' => $e->end_at?->toIso8601String(),
                    'is_full_day' => $e->is_full_day,
                ])->toArray()
            );
        }

        return $results;
    }
}
