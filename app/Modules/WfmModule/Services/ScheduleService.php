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

    public function recentWorkedDates(int $employeeId, int $count, CarbonInterface $through): array
    {
        if ($count <= 0) {
            return [];
        }

        $start = $through->copy()->subDays(60)->startOfDay();

        // 1) Fechas con programación activa (una sola consulta por rango).
        $assignments = WeeklyScheduleAssignment::with('weeklySchedule')
            ->where('employee_id', $employeeId)
            ->whereHas('weeklySchedule', function ($q) use ($start, $through) {
                $q->where('week_start_date', '<=', $through->toDateString())
                    ->where('week_end_date', '>=', $start->toDateString());
            })
            ->get()
            ->reject(fn (WeeklyScheduleAssignment $a) => $a->weeklySchedule === null)
            ->mapWithKeys(function (WeeklyScheduleAssignment $a) {
                $date = $a->weeklySchedule->week_start_date->copy()->addDays($a->day_of_week - 1);

                return [$date->toDateString() => true];
            });

        if ($assignments->isEmpty()) {
            return [];
        }

        // 2) Excepciones de día completo en el mismo rango (otra consulta).
        $fullDayExceptions = ScheduleException::where('employee_id', $employeeId)
            ->where('is_full_day', true)
            ->whereDate('start_at', '<=', $through->toDateString())
            ->whereDate('end_at', '>=', $start->toDateString())
            ->get();

        $exemptDates = [];
        foreach ($fullDayExceptions as $exception) {
            $cursor = $exception->start_at->copy()->max($start->copy());
            $end = $exception->end_at->copy()->min($through->copy());
            while ($cursor->lte($end)) {
                $exemptDates[$cursor->toDateString()] = true;
                // Reasignación obligatoria: con CarbonImmutable addDay() no muta.
                $cursor = $cursor->addDay();
            }
        }

        $worked = [];
        for ($date = $through->copy(); $date->gte($start); $date = $date->subDay()) {
            $key = $date->toDateString();
            if (($assignments[$key] ?? false) && ! isset($exemptDates[$key])) {
                $worked[] = $key;
            }
            if (count($worked) >= $count) {
                break;
            }
        }

        return array_reverse($worked);
    }
}
