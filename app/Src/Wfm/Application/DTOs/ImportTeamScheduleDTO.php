<?php

declare(strict_types=1);

namespace App\Src\Wfm\Application\DTOs;

use DateTimeImmutable;

final readonly class ImportTeamScheduleDTO
{
    public function __construct(
        public int $teamId,
        public DateTimeImmutable $weekStartDate,
        public DateTimeImmutable $weekEndDate,
        public array $rows,
        public int $importedByUserId,
    ) {}
}
