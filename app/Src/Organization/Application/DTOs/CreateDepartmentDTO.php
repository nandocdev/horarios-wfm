<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\DTOs;

final readonly class CreateDepartmentDTO
{
    public function __construct(
        public int $directorateId,
        public string $name,
        public ?string $description = null,
    ) {}
}
