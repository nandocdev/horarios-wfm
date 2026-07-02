<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\Mappers;

use App\Src\Wfm\Domain\Entities\Schedule;
use App\Src\Wfm\Domain\Entities\ScheduleAssignment;
use App\Src\Wfm\Domain\Entities\WeeklySchedule;
use App\Src\Wfm\Infrastructure\Persistence\EloquentSchedule;
use App\Src\Wfm\Infrastructure\Persistence\EloquentScheduleAssignment;
use App\Src\Wfm\Infrastructure\Persistence\EloquentWeeklySchedule;
use DateTimeImmutable;

final class ScheduleMapper
{
    public static function weeklyScheduleToDomain(EloquentWeeklySchedule $e, array $assignments = []): WeeklySchedule
    {
        return new WeeklySchedule(
            id: $e->id,
            weekStartDate: self::toImmutable($e->week_start_date),
            weekEndDate: self::toImmutable($e->week_end_date),
            status: $e->status ?? WeeklySchedule::STATUS_DRAFT,
            publishedAt: $e->published_at ? self::toImmutable($e->published_at) : null,
            assignments: $assignments,
            createdAt: self::toImmutable($e->created_at),
            updatedAt: self::toImmutable($e->updated_at),
        );
    }

    public static function assignmentToDomain(EloquentScheduleAssignment $e): ScheduleAssignment
    {
        return new ScheduleAssignment(
            id: $e->id,
            weeklyScheduleId: $e->weekly_schedule_id,
            employeeId: $e->employee_id,
            dayOfWeek: $e->day_of_week,
            startTime: $e->start_time ? self::toImmutable($e->start_time) : null,
            endTime: $e->end_time ? self::toImmutable($e->end_time) : null,
            lunchStartTime: $e->lunch_start_time ? self::toImmutable($e->lunch_start_time) : null,
            lunchEndTime: $e->lunch_end_time ? self::toImmutable($e->lunch_end_time) : null,
            breakStartTime: $e->break_start_time ? self::toImmutable($e->break_start_time) : null,
            breakEndTime: $e->break_end_time ? self::toImmutable($e->break_end_time) : null,
            scheduleId: $e->schedule_id ?? 0,
            isReplaced: (bool) ($e->is_replaced ?? false),
        );
    }

    public static function scheduleToDomain(EloquentSchedule $e): Schedule
    {
        return new Schedule(
            id: $e->id,
            name: $e->name,
            startTime: $e->start_time ? $e->start_time->format('H:i') : '',
            endTime: $e->end_time ? $e->end_time->format('H:i') : '',
            totalMinutes: (int) $e->total_minutes,
            breakMinutes: (int) ($e->break_minutes ?? 0),
            lunchMinutes: (int) ($e->lunch_minutes ?? 0),
            isLunchPaid: (bool) ($e->is_lunch_paid ?? true),
            isBreakPaid: (bool) ($e->is_break_paid ?? true),
            isActive: (bool) ($e->is_active ?? true),
            allowedDays: $e->allowed_days ?? [],
        );
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
