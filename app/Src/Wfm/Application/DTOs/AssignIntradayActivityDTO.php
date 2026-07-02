<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\DTOs;

use DateTimeImmutable;

final readonly class AssignIntradayActivityDTO
{
    public function __construct(
        public int $employeeId,
        public int $activityTypeId,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
        public ?int $approvedPeriodId = null,
        public ?string $notes = null,
    ) {}
}
