<?php

declare(strict_types=1);

namespace App\Src\Wfm\Domain\Repositories;

use App\Src\Wfm\Domain\Entities\Schedule;
use App\Src\Wfm\Domain\Entities\ScheduleAssignment;
use App\Src\Wfm\Domain\Entities\WeeklySchedule;

interface ScheduleRepositoryInterface
{
    public function saveWeeklySchedule(WeeklySchedule $weeklySchedule): WeeklySchedule;
    public function findWeeklyScheduleById(int $id): ?WeeklySchedule;
    public function findWeeklyScheduleByWeek(?\DateTimeImmutable $startDate = null): ?WeeklySchedule;
    public function findAllWeeklySchedules(): array;

    public function saveAssignmentsBatch(array $assignments, int $weeklyScheduleId): void;
    public function findAssignmentsByWeeklySchedule(int $weeklyScheduleId): array;
    public function findAssignmentsByEmployee(int $employeeId): array;

    public function saveSchedule(Schedule $schedule): Schedule;
    public function findScheduleById(int $id): ?Schedule;
    public function findAllSchedules(): array;

    public function findAssignmentsByEmployeeAndWeek(int $employeeId, \DateTimeImmutable $weekStart): array;
}
