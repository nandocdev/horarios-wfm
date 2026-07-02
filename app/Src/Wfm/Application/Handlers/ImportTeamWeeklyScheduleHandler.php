<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\Handlers;

use App\Src\Wfm\Application\DTOs\ImportTeamScheduleDTO;
use App\Src\Wfm\Application\DTOs\ScheduleRowDTO;
use App\Src\Wfm\Domain\Entities\ScheduleAssignment;
use App\Src\Wfm\Domain\Entities\WeeklySchedule;
use App\Src\Wfm\Domain\Repositories\ScheduleRepositoryInterface;
use App\Src\Wfm\Domain\Specifications\NoOverlappingAssignmentsSpecification;

final class ImportTeamWeeklyScheduleHandler
{
    public const CHUNK_SIZE = 100;

    public function __construct(
        private ScheduleRepositoryInterface $repository,
        private NoOverlappingAssignmentsSpecification $noOverlapSpec,
    ) {}

    public function handle(ImportTeamScheduleDTO $dto): WeeklySchedule
    {
        $weeklySchedule = $this->repository->findWeeklyScheduleByWeek($dto->weekStartDate);

        if ($weeklySchedule === null) {
            $weeklySchedule = WeeklySchedule::create($dto->weekStartDate, $dto->weekEndDate);
            $weeklySchedule = $this->repository->saveWeeklySchedule($weeklySchedule);
        }

        $allAssignments = [];
        $existing = $this->repository->findAssignmentsByWeeklySchedule($weeklySchedule->id());

        foreach ($dto->rows as $row) {
            $assignment = ScheduleAssignment::create(
                weeklyScheduleId: $weeklySchedule->id(),
                employeeId: $row->employeeId,
                dayOfWeek: $row->dayOfWeek,
                startTime: $row->startTime ? new \DateTimeImmutable($row->startTime) : null,
                endTime: $row->endTime ? new \DateTimeImmutable($row->endTime) : null,
                lunchStartTime: $row->lunchStart ? new \DateTimeImmutable($row->lunchStart) : null,
                lunchEndTime: $row->lunchEnd ? new \DateTimeImmutable($row->lunchEnd) : null,
                breakStartTime: $row->breakStart ? new \DateTimeImmutable($row->breakStart) : null,
                breakEndTime: $row->breakEnd ? new \DateTimeImmutable($row->breakEnd) : null,
                scheduleId: $row->scheduleId ?? 0,
            );

            if (! $this->noOverlapSpec->isSatisfiedBy($assignment, $existing)) {
                throw new \DomainException(
                    "Employee #{$row->employeeId} day {$row->dayOfWeek}: " . $this->noOverlapSpec->message()
                );
            }

            $allAssignments[] = $assignment;
            $existing[] = $assignment;
        }

        foreach (array_chunk($allAssignments, self::CHUNK_SIZE) as $chunk) {
            $this->repository->saveAssignmentsBatch($chunk, $weeklySchedule->id());
        }

        return $this->repository->findWeeklyScheduleById($weeklySchedule->id());
    }
}
