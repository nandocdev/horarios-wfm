<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Entities;

final class AppSetting
{
    private ?int $id;
    private string $key;
    private ?string $value;
    private string $type;
    private ?string $description;
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        ?int $id,
        string $key,
        ?string $value,
        string $type,
        ?string $description = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->key = $key;
        $this->value = $value;
        $this->type = $type;
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        string $key,
        ?string $value,
        string $type = 'string',
        ?string $description = null,
    ): self {
        return new self(
            id: null,
            key: $key,
            value: $value,
            type: $type,
            description: $description,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public static function fromDatabase(
        int $id,
        string $key,
        ?string $value,
        string $type,
        ?string $description = null,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            key: $key,
            value: $value,
            type: $type,
            description: $description,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateValue(?string $value): void
    {
        $this->value = $value;
    }

    public function updateDescription(?string $description): void
    {
        $this->description = $description;
    }
}
