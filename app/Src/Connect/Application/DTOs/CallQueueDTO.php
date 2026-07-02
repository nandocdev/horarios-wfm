<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class CallQueueDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public ?string $extension = null,
        public bool $isActive = true,
    ) {}
}
