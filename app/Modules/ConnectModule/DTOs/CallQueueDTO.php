<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

readonly class CallQueueDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}

    public static function fromForm(array $data): self
    {
        return new self(
            name: trim($data['name']),
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }
}
