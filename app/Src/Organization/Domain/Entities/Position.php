<?php

declare(strict_types=1);

namespace App\Src\Organization\Domain\Entities;

final class Position
{
    private ?int $id;
    private int $departmentId;
    private string $name;
    private ?string $description;
    private ?string $positionCode;
    private ?float $salary;
    private bool $isActive;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        int $departmentId,
        string $name,
        ?string $description = null,
        ?string $positionCode = null,
        ?float $salary = null,
        bool $isActive = true,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->departmentId = $departmentId;
        $this->name = $name;
        $this->description = $description;
        $this->positionCode = $positionCode;
        $this->salary = $salary;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public static function create(
        int $departmentId,
        string $name,
        ?string $description = null,
        ?string $positionCode = null,
        ?float $salary = null,
    ): self {
        return new self(null, $departmentId, $name, $description, $positionCode, $salary);
    }

    public function id(): ?int { return $this->id; }
    public function departmentId(): int { return $this->departmentId; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function positionCode(): ?string { return $this->positionCode; }
    public function salary(): ?float { return $this->salary; }
    public function isActive(): bool { return $this->isActive; }

    public function rename(string $name): void { $this->name = $name; }
    public function moveToDepartment(int $departmentId): void { $this->departmentId = $departmentId; }
    public function updateSalary(?float $salary): void { $this->salary = $salary; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
