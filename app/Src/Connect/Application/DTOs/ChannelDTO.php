<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class ChannelDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $isActive = true,
    ) {}
}
