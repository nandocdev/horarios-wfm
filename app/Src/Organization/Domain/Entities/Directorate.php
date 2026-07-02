<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Entities;

final class Directorate
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private bool $isActive;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        string $name,
        ?string $description = null,
        bool $isActive = true,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public static function create(string $name, ?string $description = null): self
    {
        return new self(null, $name, $description);
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function isActive(): bool { return $this->isActive; }

    public function rename(string $name): void { $this->name = $name; }
    public function updateDescription(?string $description): void { $this->description = $description; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
