<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Application\DTOs;

use DateTimeImmutable;

final readonly class ProcessPunchDTO
{
    public function __construct(
        public int $employeeId,
        public string $type,
        public DateTimeImmutable $punchedAt,
        public ?string $source = 'manual',
        public ?string $externalId = null,
    ) {}
}
