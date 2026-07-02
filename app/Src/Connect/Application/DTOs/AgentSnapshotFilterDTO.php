<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class AgentSnapshotFilterDTO
{
    public function __construct(
        public array $employeeIds,
        public ?string $date = null,
    ) {}
}
