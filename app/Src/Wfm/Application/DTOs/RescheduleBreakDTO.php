<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\DTOs;

use DateTimeImmutable;

final readonly class RescheduleBreakDTO
{
    public function __construct(
        public int $employeeId,
        public string $breakType,
        public DateTimeImmutable $newStartTime,
        public DateTimeImmutable $newEndTime,
        public int $requestedByUserId,
    ) {}
}
