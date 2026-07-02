<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\Mappers;

use App\Src\Wfm\Domain\Entities\ActivityType;
use App\Src\Wfm\Domain\Entities\ApprovedIntradayPeriod;
use App\Src\Wfm\Domain\Entities\IntradayActivity;
use App\Src\Wfm\Infrastructure\Persistence\EloquentActivityType;
use App\Src\Wfm\Infrastructure\Persistence\EloquentApprovedIntradayPeriod;
use App\Src\Wfm\Infrastructure\Persistence\EloquentIntradayActivity;
use DateTimeImmutable;

final class IntradayMapper
{
    public static function activityToDomain(EloquentIntradayActivity $e): IntradayActivity
    {
        $start = self::parseRangeStart($e->time_range) ?? new DateTimeImmutable();
        $end = self::parseRangeEnd($e->time_range) ?? new DateTimeImmutable()->modify('+1 hour');

        return new IntradayActivity(
            id: $e->id,
            employeeId: $e->employee_id,
            activityTypeId: $e->activity_type_id,
            startTime: $start,
            endTime: $end,
            approvedPeriodId: $e->approved_period_id,
            notes: $e->notes,
        );
    }

    public static function activityTypeToDomain(EloquentActivityType $e): ActivityType
    {
        return new ActivityType(
            id: $e->id,
            name: $e->name,
            color: $e->color ?? '#f59e0b',
            isProductive: (bool) ($e->is_productive ?? false),
            isPaid: (bool) ($e->is_paid ?? true),
        );
    }

    public static function approvedPeriodToDomain(EloquentApprovedIntradayPeriod $e): ApprovedIntradayPeriod
    {
        return new ApprovedIntradayPeriod(
            id: $e->id,
            teamId: $e->team_id,
            activityDefinitionId: $e->activity_definition_id,
            date: self::toImmutable($e->date),
            startTime: $e->start_time,
            endTime: $e->end_time,
            maxSlots: (int) $e->max_slots,
            notes: $e->notes,
            usedSlots: 0,
        );
    }

    private static function parseRangeStart(?string $range): ?DateTimeImmutable
    {
        if (empty($range)) return null;
        $clean = str_replace(['[', '(', ']', ')', '"'], '', $range);
        $parts = explode(',', $clean);
        $val = trim($parts[0] ?? '');
        return $val ? new DateTimeImmutable($val) : null;
    }

    private static function parseRangeEnd(?string $range): ?DateTimeImmutable
    {
        if (empty($range)) return null;
        $clean = str_replace(['[', '(', ']', ')', '"'], '', $range);
        $parts = explode(',', $clean);
        $val = trim($parts[1] ?? '');
        return $val ? new DateTimeImmutable($val) : null;
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
