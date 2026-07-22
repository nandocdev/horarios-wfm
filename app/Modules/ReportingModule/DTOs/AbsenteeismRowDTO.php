<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class AbsenteeismRowDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $employeeNumber,
        public ?string $teamName,
        public string $date,
        public string $originType,
        public string $causeName,
        public bool $isJustified,
        public bool $isFullDay,
        public ?string $startAt,
        public ?string $endAt,
        public ?int $minutesAbsent,
        public ?string $remarks,
    ) {}
}
