<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Domain\Services;

use App\Src\TimeAndAttendance\Domain\Entities\AttendanceIncident;
use App\Src\TimeAndAttendance\Domain\Entities\AttendancePunch;
use App\Src\TimeAndAttendance\Domain\Entities\IncidentType;
use App\Src\TimeAndAttendance\Domain\Events\AttendanceIncidentRecorded;
use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;
use DateTimeImmutable;

final class EndOfDayReconciliationService
{
    private const LATE_THRESHOLD_MINUTES = 5;

    public function __construct(
        private AttendanceRepositoryInterface $repository,
    ) {}

    public function reconcile(int $employeeId, DateTimeImmutable $date): array
    {
        $createdIncidents = [];
        $punches = $this->repository->findPunchesByEmployee($employeeId, $date);
        $existingIncidents = $this->repository->findIncidentsByEmployee($employeeId, $date);
        $existingCodes = array_map(fn (AttendanceIncident $i) => $i->incidentTypeCode(), $existingIncidents);

        $entryPunch = $this->findFirstByType($punches, AttendancePunch::TYPE_ENTRY);
        $exitPunch = $this->findLastByType($punches, AttendancePunch::TYPE_EXIT);

        $hasLunchPunch = ! empty($this->filterByType($punches, AttendancePunch::TYPE_LUNCH_START));
        $hasBreakPunch = ! empty($this->filterByType($punches, AttendancePunch::TYPE_BREAK_START));

        if (empty($punches) && ! in_array(IncidentType::CODE_ABSENT, $existingCodes, true)) {
            $incident = $this->createIncident($employeeId, IncidentType::CODE_ABSENT, $date);
            $createdIncidents[] = $incident;
        }

        if ($entryPunch && ! in_array(IncidentType::CODE_LATE, $existingCodes, true)) {
            $diffMinutes = $this->minutesDiff($entryPunch->punchedAt(), $date);
            if ($diffMinutes > self::LATE_THRESHOLD_MINUTES) {
                $incident = $this->createIncident($employeeId, IncidentType::CODE_LATE, $date, $entryPunch->punchedAt());
                $createdIncidents[] = $incident;
            }
        }

        if ($entryPunch && ! $exitPunch && ! in_array(IncidentType::CODE_EARLY_EXIT, $existingCodes, true) && ! in_array(IncidentType::CODE_ABSENT, $existingCodes, true)) {
            $incident = $this->createIncident($employeeId, IncidentType::CODE_EARLY_EXIT, $date);
            $createdIncidents[] = $incident;
        }

        return $createdIncidents;
    }

    public function reconcileAll(DateTimeImmutable $date): array
    {
        return [];
    }

    private function createIncident(int $employeeId, string $typeCode, DateTimeImmutable $date, ?DateTimeImmutable $time = null): AttendanceIncident
    {
        $incident = AttendanceIncident::create($employeeId, $typeCode, $date, $time);
        $saved = $this->repository->saveIncident($incident);
        event(new AttendanceIncidentRecorded($saved));
        return $saved;
    }

    private function findFirstByType(array $punches, string $type): ?AttendancePunch
    {
        foreach ($punches as $p) {
            if ($p->type() === $type) return $p;
        }
        return null;
    }

    private function findLastByType(array $punches, string $type): ?AttendancePunch
    {
        $last = null;
        foreach ($punches as $p) {
            if ($p->type() === $type && ($last === null || $p->punchedAt() > $last->punchedAt())) {
                $last = $p;
            }
        }
        return $last;
    }

    private function filterByType(array $punches, string $type): array
    {
        return array_values(array_filter($punches, fn ($p) => $p->type() === $type));
    }

    private function minutesDiff(DateTimeImmutable $actual, DateTimeImmutable $expected): int
    {
        return (int) (($actual->getTimestamp() - $expected->getTimestamp()) / 60);
    }
}
