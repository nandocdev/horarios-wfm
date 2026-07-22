<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class LeaveRowDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $employeeNumber,
        public ?string $teamName,
        public string $date,
        public string $leaveType,
        public bool $isExcused,
        public string $status,
        public ?int $minutes,
        public ?string $remarks,
    ) {}
}
