<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Entities;

final class Role
{
    private ?int $id;
    private string $name;
    private string $code;
    private int $hierarchyLevel;
    private string $guardName;
    private array $permissions;
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        ?int $id,
        string $name,
        string $code,
        int $hierarchyLevel,
        string $guardName,
        array $permissions = [],
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->hierarchyLevel = $hierarchyLevel;
        $this->guardName = $guardName;
        $this->permissions = $permissions;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        string $name,
        string $code,
        int $hierarchyLevel,
        string $guardName = 'web',
        array $permissions = [],
    ): self {
        return new self(
            id: null,
            name: $name,
            code: strtoupper($code),
            hierarchyLevel: $hierarchyLevel,
            guardName: $guardName,
            permissions: $permissions,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public static function fromDatabase(
        int $id,
        string $name,
        string $code,
        int $hierarchyLevel,
        string $guardName,
        array $permissions = [],
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            code: $code,
            hierarchyLevel: $hierarchyLevel,
            guardName: $guardName,
            permissions: $permissions,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function hierarchyLevel(): int
    {
        return $this->hierarchyLevel;
    }

    public function guardName(): string
    {
        return $this->guardName;
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasPermission(string $permission): bool
    {
        foreach ($this->permissions as $perm) {
            if ($perm instanceof Permission && $perm->name() === $permission) {
                return true;
            }
        }

        return false;
    }

    public function addPermission(Permission $permission): void
    {
        if (! $this->hasPermission($permission->name())) {
            $this->permissions[] = $permission;
        }
    }

    public function removePermission(Permission $permission): void
    {
        $this->permissions = array_values(
            array_filter(
                $this->permissions,
                fn ($p) => $p instanceof Permission && $p->name() !== $permission->name(),
            ),
        );
    }

    public function isHigherThan(Role $other): bool
    {
        return $this->hierarchyLevel < $other->hierarchyLevel;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function setHierarchyLevel(int $level): void
    {
        $this->hierarchyLevel = $level;
    }
}
