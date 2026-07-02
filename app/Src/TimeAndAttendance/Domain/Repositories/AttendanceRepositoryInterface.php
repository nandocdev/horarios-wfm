<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Repositories;

use App\Src\TimeAndAttendance\Domain\Entities\AttendanceIncident;
use App\Src\TimeAndAttendance\Domain\Entities\AttendancePunch;
use DateTimeImmutable;

interface AttendanceRepositoryInterface
{
    public function savePunch(AttendancePunch $punch): AttendancePunch;
    public function findPunchesByEmployee(int $employeeId, DateTimeImmutable $date): array;

    public function saveIncident(AttendanceIncident $incident): AttendanceIncident;
    public function findIncidentById(int $id): ?AttendanceIncident;
    public function findIncidentsByEmployee(int $employeeId, DateTimeImmutable $date): array;
    public function findIncidentByTypeAndDate(int $employeeId, string $typeCode, DateTimeImmutable $date): ?AttendanceIncident;
    public function findIncidentsByDate(DateTimeImmutable $date): array;
}
