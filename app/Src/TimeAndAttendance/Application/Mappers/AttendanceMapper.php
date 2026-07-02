<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Application\Mappers;

use App\Src\TimeAndAttendance\Domain\Entities\AttendanceIncident;
use App\Src\TimeAndAttendance\Domain\Entities\AttendancePunch;
use App\Src\TimeAndAttendance\Infrastructure\Persistence\EloquentAttendanceIncident;
use App\Src\TimeAndAttendance\Infrastructure\Persistence\EloquentAttendancePunch;
use DateTimeImmutable;

final class AttendanceMapper
{
    public static function punchToDomain(EloquentAttendancePunch $e): AttendancePunch
    {
        return new AttendancePunch(
            id: $e->id,
            employeeId: $e->employee_id,
            type: $e->type,
            punchedAt: self::toImmutable($e->punched_at),
            source: $e->source,
            externalId: $e->external_id,
        );
    }

    public static function punchToEloquent(AttendancePunch $p): array
    {
        return [
            'employee_id' => $p->employeeId(),
            'type' => $p->type(),
            'punched_at' => $p->punchedAt()->format('Y-m-d H:i:s'),
            'source' => $p->source(),
            'external_id' => $p->externalId(),
        ];
    }

    public static function incidentToDomain(EloquentAttendanceIncident $e, string $typeCode): AttendanceIncident
    {
        return new AttendanceIncident(
            id: $e->id,
            employeeId: $e->employee_id,
            incidentTypeCode: $typeCode,
            incidentDate: self::toImmutable($e->incident_date),
            startTime: $e->start_time ? self::toImmutable($e->start_time) : null,
            endTime: $e->end_time ? self::toImmutable($e->end_time) : null,
            status: $e->status ?? AttendanceIncident::STATUS_OPEN,
            userComment: $e->user_comment,
            adminComment: $e->admin_comment,
            resolvedByUserId: $e->resolved_by_user_id,
            resolvedAt: $e->resolved_at ? self::toImmutable($e->resolved_at) : null,
        );
    }

    public static function incidentToEloquent(AttendanceIncident $i): array
    {
        return [
            'employee_id' => $i->employeeId(),
            'incident_type_id' => 0,
            'incident_date' => $i->incidentDate()->format('Y-m-d'),
            'start_time' => $i->startTime()?->format('H:i:s'),
            'end_time' => $i->endTime()?->format('H:i:s'),
            'status' => $i->status(),
            'user_comment' => $i->userComment(),
            'admin_comment' => $i->adminComment(),
        ];
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
