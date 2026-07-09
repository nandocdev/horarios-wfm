<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Schedules;

use App\Shared\DTOs\Schedules\ScheduleDayDTO;
use Carbon\CarbonInterface;

interface ScheduleRepositoryInterface
{
    public function getForEmployee(int $employeeId, CarbonInterface $date): ScheduleDayDTO;

    /**
     * @param  int[]  $employeeIds
     * @return array<int, ScheduleDayDTO>
     */
    public function getForEmployees(array $employeeIds, CarbonInterface $date): array;

    /**
     * @return ScheduleDayDTO[]
     */
    public function getForDateRange(int $employeeId, CarbonInterface $start, CarbonInterface $end): array;
}
