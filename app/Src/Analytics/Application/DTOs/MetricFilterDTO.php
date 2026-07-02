<?php

declare(strict_types=1);

namespace App\Src\Analytics\Application\DTOs;

use DateTimeImmutable;

final readonly class MetricFilterDTO
{
    public function __construct(
        public ?int $employeeId = null,
        public ?int $teamId = null,
        public ?DateTimeImmutable $startDate = null,
        public ?DateTimeImmutable $endDate = null,
    ) {}
}
