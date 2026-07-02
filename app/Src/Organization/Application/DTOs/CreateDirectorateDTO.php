<?php

declare(strict_types=1);

namespace App\Src\Organization\Application\DTOs;

final readonly class CreateDirectorateDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
