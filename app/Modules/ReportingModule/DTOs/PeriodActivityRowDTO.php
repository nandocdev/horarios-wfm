<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class PeriodActivityRowDTO
{
    public function __construct(
        public string $entityName,
        public string $entityType,
        public string $activityName,
        public int $totalMinutes,
        public bool $isProductive,
        public ?float $compliancePct,
    ) {}
}
