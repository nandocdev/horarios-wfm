<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Application\DTOs;

final readonly class AttendanceSummaryDTO
{
    public function __construct(
        public int $employeeId,
        public string $date,
        public ?string $expectedEntry,
        public ?string $actualEntry,
        public int $diffMinutes,
        public string $status,
    ) {}
}
