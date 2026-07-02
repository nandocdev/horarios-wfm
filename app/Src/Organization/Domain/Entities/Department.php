<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Entities;

final class Department
{
    private ?int $id;
    private int $directorateId;
    private string $name;
    private ?string $description;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        int $directorateId,
        string $name,
        ?string $description = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->directorateId = $directorateId;
        $this->name = $name;
        $this->description = $description;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public static function create(int $directorateId, string $name, ?string $description = null): self
    {
        return new self(null, $directorateId, $name, $description);
    }

    public function id(): ?int { return $this->id; }
    public function directorateId(): int { return $this->directorateId; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }

    public function rename(string $name): void { $this->name = $name; }
    public function moveToDirectorate(int $directorateId): void { $this->directorateId = $directorateId; }
    public function updateDescription(?string $description): void { $this->description = $description; }
}
