<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\DTOs;

final readonly class CreatePositionDTO
{
    public function __construct(
        public int $departmentId,
        public string $name,
        public ?string $description = null,
        public ?string $positionCode = null,
        public ?float $salary = null,
    ) {}
}
