<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class TeamPerformanceRowDTO
{
    public function __construct(
        public string $teamName,
        public int $agentCount,
        public int $totalCalls,
        public float $avgAht,
        public float $avgOccupancy,
        public float $avgAdherence,
    ) {}
}
