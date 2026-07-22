<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class VacationRowDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $employeeNumber,
        public ?string $teamName,
        public string $startDate,
        public string $endDate,
        public int $daysTaken,
        public ?string $remarks,
    ) {}
}
