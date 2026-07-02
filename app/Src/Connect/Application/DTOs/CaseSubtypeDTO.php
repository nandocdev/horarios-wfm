<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class CaseSubtypeDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $isActive = true,
    ) {}
}
