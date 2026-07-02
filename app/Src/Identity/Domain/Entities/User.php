<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Entities;

use App\Src\Identity\Domain\ValueObjects\IdentityRole;
use App\Src\Identity\Domain\ValueObjects\Password;
use App\Src\Shared\Domain\ValueObjects\Email;

final class User
{
    private ?int $id;
    private string $name;
    private Email $email;
    private Password $password;
    private bool $isActive;
    private bool $forcePasswordChange;
    private ?\DateTimeImmutable $lastLoginAt;
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private array $roles;

    private function __construct(
        ?int $id,
        string $name,
        Email $email,
        Password $password,
        bool $isActive,
        bool $forcePasswordChange,
        ?\DateTimeImmutable $lastLoginAt,
        ?\DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $updatedAt,
        array $roles = [],
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->isActive = $isActive;
        $this->forcePasswordChange = $forcePasswordChange;
        $this->lastLoginAt = $lastLoginAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->roles = $roles;
    }

    public static function create(
        string $name,
        Email $email,
        Password $password,
        bool $isActive = true,
        bool $forcePasswordChange = false,
        array $roles = [],
    ): self {
        return new self(
            id: null,
            name: $name,
            email: $email,
            password: $password,
            isActive: $isActive,
            forcePasswordChange: $forcePasswordChange,
            lastLoginAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            roles: $roles,
        );
    }

    public static function fromDatabase(
        int $id,
        string $name,
        Email $email,
        Password $password,
        bool $isActive,
        bool $forcePasswordChange,
        ?\DateTimeImmutable $lastLoginAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        array $roles = [],
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            password: $password,
            isActive: $isActive,
            forcePasswordChange: $forcePasswordChange,
            lastLoginAt: $lastLoginAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            roles: $roles,
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

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): Password
    {
        return $this->password;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function forcePasswordChange(): bool
    {
        return $this->forcePasswordChange;
    }

    public function lastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function roles(): array
    {
        return $this->roles;
    }

    public function markLastLogin(\DateTimeImmutable $timestamp): void
    {
        $this->lastLoginAt = $timestamp;

        if ($this->forcePasswordChange) {
            $this->forcePasswordChange = false;
        }
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function updatePassword(Password $password): void
    {
        $this->password = $password;
        $this->forcePasswordChange = false;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function hasRole(string $roleCode): bool
    {
        foreach ($this->roles as $role) {
            if ($role instanceof IdentityRole && $role->code() === $roleCode) {
                return true;
            }
        }

        return false;
    }

    public function maxHierarchyLevel(): int
    {
        $levels = array_map(
            fn ($role) => $role instanceof IdentityRole ? $role->hierarchyLevel() : 0,
            $this->roles,
        );

        return empty($levels) ? 0 : min($levels);
    }

    public function initials(): string
    {
        $parts = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            if (! empty($part)) {
                $initials .= strtoupper($part[0]);
            }
        }

        return $initials;
    }
}
