<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

final readonly class PermissionDTO
{
    public function __construct(
        public string $name,
        public string $guardName = 'web',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            guardName: $data['guard_name'] ?? 'web',
        );
    }
}
