<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\DTOs;

final readonly class CreateTeamDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $supervisorId = null,
        public ?int $baseScheduleId = null,
        public ?string $ciscoTeamId = null,
        public array $memberIds = [],
    ) {}
}
