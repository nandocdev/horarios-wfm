<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\ValueObjects;

final class IdentityRole {
    public function __construct(
        private readonly string $name,
        private readonly string $code,
        private readonly int $hierarchyLevel,
        private readonly string $guardName = 'web',
    ) {
    }

    public static function fromArray(array $data): self {
        return new self(
            name: $data['name'],
            code: strtoupper($data['code'] ?? $data['name']),
            hierarchyLevel: (int) ($data['hierarchy_level'] ?? 0),
            guardName: $data['guard_name'] ?? 'web',
        );
    }

    public function name(): string {
        return $this->name;
    }

    public function code(): string {
        return $this->code;
    }

    public function hierarchyLevel(): int {
        return $this->hierarchyLevel;
    }

    public function guardName(): string {
        return $this->guardName;
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'hierarchy_level' => $this->hierarchyLevel,
            'guard_name' => $this->guardName,
        ];
    }
}
