<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Schedules;

use App\Shared\DTOs\Schedules\ScheduleDayDTO;
use Carbon\CarbonInterface;

interface ScheduleServiceInterface
{
    public function getScheduleForEmployee(int $employeeId, CarbonInterface $date): ScheduleDayDTO;

    public function getBatchSchedules(array $employeeIds, CarbonInterface $date): array;
}
