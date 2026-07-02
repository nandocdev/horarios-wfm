<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Entities;

final class Permission
{
    private ?int $id;
    private string $name;
    private string $guardName;
    private ?\DateTimeImmutable $createdAt;

    private function __construct(
        ?int $id,
        string $name,
        string $guardName = 'web',
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->guardName = $guardName;
        $this->createdAt = $createdAt;
    }

    public static function create(
        string $name,
        string $guardName = 'web',
    ): self {
        return new self(
            id: null,
            name: $name,
            guardName: $guardName,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public static function fromDatabase(
        int $id,
        string $name,
        string $guardName = 'web',
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            guardName: $guardName,
            createdAt: $createdAt,
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

    public function guardName(): string
    {
        return $this->guardName;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
