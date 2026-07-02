<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Persistence;

use App\Src\TimeAndAttendance\Application\Mappers\AttendanceMapper;
use App\Src\TimeAndAttendance\Domain\Entities\AttendanceIncident;
use App\Src\TimeAndAttendance\Domain\Entities\AttendancePunch;
use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;
use DateTimeImmutable;

final class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function savePunch(AttendancePunch $punch): AttendancePunch
    {
        $eloquent = EloquentAttendancePunch::create(
            AttendanceMapper::punchToEloquent($punch),
        );

        return AttendanceMapper::punchToDomain($eloquent);
    }

    public function findPunchesByEmployee(int $employeeId, DateTimeImmutable $date): array
    {
        $start = $date->format('Y-m-d 00:00:00');
        $end = $date->format('Y-m-d 23:59:59');

        return EloquentAttendancePunch::where('employee_id', $employeeId)
            ->whereBetween('punched_at', [$start, $end])
            ->orderBy('punched_at')
            ->get()
            ->map(fn (EloquentAttendancePunch $e) => AttendanceMapper::punchToDomain($e))
            ->toArray();
    }

    public function saveIncident(AttendanceIncident $incident): AttendanceIncident
    {
        $eloquent = EloquentAttendanceIncident::updateOrCreate(
            ['id' => $incident->id()],
            AttendanceMapper::incidentToEloquent($incident),
        );

        return AttendanceMapper::incidentToDomain($eloquent, $incident->incidentTypeCode());
    }

    public function findIncidentsByEmployee(int $employeeId, DateTimeImmutable $date): array
    {
        return EloquentAttendanceIncident::where('employee_id', $employeeId)
            ->whereDate('incident_date', $date->format('Y-m-d'))
            ->get()
            ->map(fn (EloquentAttendanceIncident $e) => AttendanceMapper::incidentToDomain($e, ''))
            ->toArray();
    }

    public function findIncidentByTypeAndDate(int $employeeId, string $typeCode, DateTimeImmutable $date): ?AttendanceIncident
    {
        $eloquent = EloquentAttendanceIncident::where('employee_id', $employeeId)
            ->whereDate('incident_date', $date->format('Y-m-d'))
            ->first();

        return $eloquent ? AttendanceMapper::incidentToDomain($eloquent, $typeCode) : null;
    }

    public function findIncidentsByDate(DateTimeImmutable $date): array
    {
        return EloquentAttendanceIncident::whereDate('incident_date', $date->format('Y-m-d'))
            ->get()
            ->map(fn (EloquentAttendanceIncident $e) => AttendanceMapper::incidentToDomain($e, ''))
            ->toArray();
    }
}
