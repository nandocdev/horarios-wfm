<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\DTOs;

final readonly class ScheduleRowDTO
{
    public function __construct(
        public int $employeeId,
        public int $dayOfWeek,
        public ?string $startTime,
        public ?string $endTime,
        public ?string $lunchStart = null,
        public ?string $lunchEnd = null,
        public ?string $breakStart = null,
        public ?string $breakEnd = null,
        public ?int $scheduleId = null,
    ) {}
}
