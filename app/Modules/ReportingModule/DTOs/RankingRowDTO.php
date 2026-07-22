<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class RankingRowDTO
{
    public function __construct(
        public int $position,
        public int $employeeId,
        public string $employeeName,
        public string $employeeNumber,
        public ?string $teamName,
        public int $callsHandled,
        public float $aht,
        public float $occupancy,
        public float $adherence,
        public float $score,
    ) {}
}
