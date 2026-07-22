<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class TardinessRowDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $employeeNumber,
        public ?string $teamName,
        public string $date,
        public ?string $scheduledStart,
        public ?string $actualLogin,
        public ?int $minutesLate,
        public ?string $incidentType,
        public ?string $justification,
    ) {}
}
