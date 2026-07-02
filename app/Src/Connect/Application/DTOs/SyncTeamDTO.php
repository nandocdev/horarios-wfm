<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class SyncTeamDTO
{
    public function __construct(
        public string $teamId,
        public string $name,
        public ?string $description = null,
    ) {}
}
