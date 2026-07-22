<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

readonly class IntradayActivityRowDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $date,
        public string $startTime,
        public string $endTime,
        public string $activityName,
        public bool $isProductive,
        public ?string $notes,
    ) {}
}
