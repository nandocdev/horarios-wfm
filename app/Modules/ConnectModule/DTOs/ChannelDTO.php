<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

readonly class ChannelDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            is_active: $data['is_active'] ?? true,
        );
    }
}
