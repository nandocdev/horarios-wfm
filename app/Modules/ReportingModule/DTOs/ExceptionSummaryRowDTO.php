<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class ExceptionSummaryRowDTO
{
    public function __construct(
        public string $causeName,
        public string $shortCode,
        public bool $isExcused,
        public int $totalOccurrences,
        public int $totalMinutesLost,
        public int $employeesAffected,
        public ?float $percentageOfScheduled = null,
        public ?array $breakdownByTeam = null,
    ) {}
}
