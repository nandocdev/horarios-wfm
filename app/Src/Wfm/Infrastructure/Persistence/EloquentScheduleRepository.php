<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use App\Src\Wfm\Application\Mappers\ScheduleMapper;
use App\Src\Wfm\Domain\Entities\Schedule;
use App\Src\Wfm\Domain\Entities\ScheduleAssignment;
use App\Src\Wfm\Domain\Entities\WeeklySchedule;
use App\Src\Wfm\Domain\Repositories\ScheduleRepositoryInterface;
use DateTimeImmutable;

final class EloquentScheduleRepository implements ScheduleRepositoryInterface
{
    public function saveWeeklySchedule(WeeklySchedule $weeklySchedule): WeeklySchedule
    {
        $eloquent = EloquentWeeklySchedule::updateOrCreate(
            ['id' => $weeklySchedule->id()],
            [
                'week_start_date' => $weeklySchedule->weekStartDate()->format('Y-m-d'),
                'week_end_date' => $weeklySchedule->weekEndDate()->format('Y-m-d'),
                'status' => $weeklySchedule->status(),
                'published_at' => $weeklySchedule->publishedAt()?->format('Y-m-d H:i:s'),
            ],
        );

        return ScheduleMapper::weeklyScheduleToDomain($eloquent);
    }

    public function findWeeklyScheduleById(int $id): ?WeeklySchedule
    {
        $eloquent = EloquentWeeklySchedule::find($id);
        if (! $eloquent) return null;

        $assignments = $this->findAssignmentsByWeeklySchedule($id);

        return ScheduleMapper::weeklyScheduleToDomain($eloquent, $assignments);
    }

    public function findWeeklyScheduleByWeek(?\DateTimeImmutable $startDate = null): ?WeeklySchedule
    {
        $date = $startDate ?? new DateTimeImmutable('monday this week');
        $eloquent = EloquentWeeklySchedule::where('week_start_date', $date->format('Y-m-d'))->first();

        if (! $eloquent) return null;

        return ScheduleMapper::weeklyScheduleToDomain($eloquent);
    }

    public function findAllWeeklySchedules(): array
    {
        return EloquentWeeklySchedule::latest()->get()
            ->map(fn (EloquentWeeklySchedule $e) => ScheduleMapper::weeklyScheduleToDomain($e))
            ->toArray();
    }

    public function saveAssignmentsBatch(array $assignments, int $weeklyScheduleId): void
    {
        $data = [];
        foreach ($assignments as $a) {
            if (! $a instanceof ScheduleAssignment) continue;
            $data[] = [
                'weekly_schedule_id' => $weeklyScheduleId,
                'employee_id' => $a->employeeId(),
                'schedule_id' => $a->scheduleId(),
                'day_of_week' => $a->dayOfWeek(),
                'start_time' => $a->startTime()?->format('H:i:s'),
                'end_time' => $a->endTime()?->format('H:i:s'),
                'lunch_start_time' => $a->lunchStartTime()?->format('H:i:s'),
                'lunch_end_time' => $a->lunchEndTime()?->format('H:i:s'),
                'break_start_time' => $a->breakStartTime()?->format('H:i:s'),
                'break_end_time' => $a->breakEndTime()?->format('H:i:s'),
                'is_replaced' => $a->isReplaced(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($data)) {
            EloquentScheduleAssignment::insert($data);
        }
    }

    public function findAssignmentsByWeeklySchedule(int $weeklyScheduleId): array
    {
        return EloquentScheduleAssignment::where('weekly_schedule_id', $weeklyScheduleId)->get()
            ->map(fn (EloquentScheduleAssignment $e) => ScheduleMapper::assignmentToDomain($e))
            ->toArray();
    }

    public function findAssignmentsByEmployee(int $employeeId): array
    {
        return EloquentScheduleAssignment::where('employee_id', $employeeId)->get()
            ->map(fn (EloquentScheduleAssignment $e) => ScheduleMapper::assignmentToDomain($e))
            ->toArray();
    }

    public function saveSchedule(Schedule $schedule): Schedule
    {
        $eloquent = EloquentSchedule::updateOrCreate(
            ['id' => $schedule->id()],
            [
                'name' => $schedule->name(),
                'start_time' => $schedule->startTime(),
                'end_time' => $schedule->endTime(),
                'total_minutes' => $schedule->totalMinutes(),
                'break_minutes' => $schedule->breakMinutes(),
                'lunch_minutes' => $schedule->lunchMinutes(),
                'is_lunch_paid' => $schedule->isLunchPaid(),
                'is_break_paid' => $schedule->isBreakPaid(),
                'is_active' => $schedule->isActive(),
                'allowed_days' => $schedule->allowedDays(),
            ],
        );

        return ScheduleMapper::scheduleToDomain($eloquent);
    }

    public function findScheduleById(int $id): ?Schedule
    {
        $eloquent = EloquentSchedule::find($id);
        return $eloquent ? ScheduleMapper::scheduleToDomain($eloquent) : null;
    }

    public function findAllSchedules(): array
    {
        return EloquentSchedule::where('is_active', true)->get()
            ->map(fn (EloquentSchedule $e) => ScheduleMapper::scheduleToDomain($e))
            ->toArray();
    }

    public function findAssignmentsByEmployeeAndWeek(int $employeeId, DateTimeImmutable $weekStart): array
    {
        $schedule = $this->findWeeklyScheduleByWeek($weekStart);
        if (! $schedule) return [];

        return EloquentScheduleAssignment::where('weekly_schedule_id', $schedule->id())
            ->where('employee_id', $employeeId)
            ->get()
            ->map(fn (EloquentScheduleAssignment $e) => ScheduleMapper::assignmentToDomain($e))
            ->toArray();
    }
}
