<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class AgentPerformanceRowDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $employeeNumber,
        public ?string $teamName,
        public int $callsHandled,
        public float $aht,
        public float $occupancy,
        public float $talkTime,
        public float $readyTime,
        public float $acwTime,
    ) {}
}
