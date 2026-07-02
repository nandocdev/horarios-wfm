<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

final readonly class RoleDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public int $hierarchyLevel,
        public string $guardName = 'web',
        public array $permissionNames = [],
        public ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            code: strtoupper($data['code'] ?? $data['name']),
            hierarchyLevel: (int) ($data['hierarchy_level'] ?? 0),
            guardName: $data['guard_name'] ?? 'web',
            permissionNames: $data['permissions'] ?? [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
