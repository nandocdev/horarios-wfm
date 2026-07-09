<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\DTOs;

use Carbon\CarbonInterface;

final readonly class CreateLeaveRequestDTO
{
    public function __construct(
        public int $employeeId,
        public string $type,
        public CarbonInterface $startTime,
        public CarbonInterface $endTime,
        public int $minutes,
        public string $reason
    ) {}
}
