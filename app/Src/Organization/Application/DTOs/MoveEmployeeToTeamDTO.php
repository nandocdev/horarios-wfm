<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\DTOs;

use DateTimeImmutable;

final readonly class MoveEmployeeToTeamDTO
{
    public function __construct(
        public int $employeeId,
        public int $targetTeamId,
        public ?DateTimeImmutable $effectiveDate = null,
    ) {}
}
