<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Application\Handlers;

use App\Src\TimeAndAttendance\Application\DTOs\AttendanceSummaryDTO;
use App\Src\TimeAndAttendance\Domain\Entities\AttendanceIncident;
use App\Src\TimeAndAttendance\Domain\Entities\IncidentType;
use App\Src\TimeAndAttendance\Domain\Events\AttendanceIncidentRecorded;
use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;
use DateTimeImmutable;

final class ReconcileAttendanceHandler
{
    private const LATE_THRESHOLD_MINUTES = 5;

    public function __construct(
        private AttendanceRepositoryInterface $repository,
    ) {}

    public function handle(int $employeeId, DateTimeImmutable $date): AttendanceSummaryDTO
    {
        $punches = $this->repository->findPunchesByEmployee($employeeId, $date);

        $entryPunch = $this->findFirstPunchByType($punches, AttendancePunch::TYPE_ENTRY);
        $exitPunch = $this->findLastPunchByType($punches, AttendancePunch::TYPE_EXIT);

        $expectedEntry = null;
        $actualEntry = $entryPunch?->punchedAt();
        $diffMinutes = 0;
        $status = 'present';

        $existing = $this->repository->findIncidentsByEmployee($employeeId, $date);

        foreach ($existing as $incident) {
            if ($incident->isOpen()) {
                $status = $incident->incidentTypeCode() === IncidentType::CODE_LATE ? 'late' : 'absent';
            }
        }

        if ($entryPunch && $expectedEntry) {
            $diffMinutes = (int) ($entryPunch->punchedAt()->getTimestamp() - $expectedEntry->getTimestamp()) / 60;

            if ($diffMinutes > self::LATE_THRESHOLD_MINUTES && $status === 'present') {
                $this->recordIncident($employeeId, IncidentType::CODE_LATE, $date, $entryPunch->punchedAt());
                $status = 'late';
            }
        }

        if (empty($punches)) {
            $existingAbsent = $this->repository->findIncidentByTypeAndDate(
                $employeeId, IncidentType::CODE_ABSENT, $date
            );

            if (! $existingAbsent) {
                $this->recordIncident($employeeId, IncidentType::CODE_ABSENT, $date);
            }
            $status = 'absent';
        }

        return new AttendanceSummaryDTO(
            employeeId: $employeeId,
            date: $date->format('Y-m-d'),
            expectedEntry: $expectedEntry?->format('H:i'),
            actualEntry: $actualEntry?->format('H:i'),
            diffMinutes: $diffMinutes,
            status: $status,
        );
    }

    private function recordIncident(int $employeeId, string $typeCode, DateTimeImmutable $date, ?DateTimeImmutable $time = null): AttendanceIncident
    {
        $incident = AttendanceIncident::create($employeeId, $typeCode, $date, $time);
        $saved = $this->repository->saveIncident($incident);
        event(new AttendanceIncidentRecorded($saved));

        return $saved;
    }

    private function findFirstPunchByType(array $punches, string $type): ?AttendancePunch
    {
        $filtered = array_values(array_filter(
            $punches,
            fn (AttendancePunch $p) => $p->type() === $type,
        ));

        return $filtered[0] ?? null;
    }

    private function findLastPunchByType(array $punches, string $type): ?AttendancePunch
    {
        $filtered = array_filter(
            $punches,
            fn (AttendancePunch $p) => $p->type() === $type,
        );

        $last = null;
        foreach ($filtered as $p) {
            if ($last === null || $p->punchedAt() > $last->punchedAt()) {
                $last = $p;
            }
        }

        return $last;
    }
}
