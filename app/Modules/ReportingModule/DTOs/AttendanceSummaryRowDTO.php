<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class AttendanceSummaryRowDTO
{
    public function __construct(
        public string $entityName,
        public string $entityType,
        public int $totalScheduledDays,
        public int $totalAbsences,
        public int $totalTardiness,
        public int $totalLeaves,
        public int $totalVacationDays,
        public float $attendanceRate,
        public float $tardinessRate,
    ) {}
}
