<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\DTOs;

use App\Modules\ReportingModule\Enums\ReportFormatEnum;

readonly class ReportFilterDTO
{
    public function __construct(
        public string $dateFrom,
        public string $dateTo,
        public ReportFormatEnum $format,
        public ?int $teamId = null,
        public ?int $employeeId = null,
        public ?int $queueId = null,
        public ?bool $justified = null,
        public ?string $originType = null,
        public string $interval = 'daily',
    ) {}
}
