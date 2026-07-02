<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

final readonly class AppSettingDTO
{
    public function __construct(
        public string $key,
        public ?string $value,
        public string $type = 'string',
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            value: $data['value'] ?? null,
            type: $data['type'] ?? 'string',
            description: $data['description'] ?? null,
        );
    }
}
