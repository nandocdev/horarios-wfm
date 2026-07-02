<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\Entities;

use DateTimeImmutable;

final class EmployeeDependent
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $employeeId,
        private readonly string $name,
        private readonly string $relationship,
        private readonly ?DateTimeImmutable $birthDate,
    ) {}

    public static function register(int $employeeId, string $name, string $relationship, ?DateTimeImmutable $birthDate = null): self
    {
        return new self(null, $employeeId, $name, $relationship, $birthDate);
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function name(): string { return $this->name; }
    public function relationship(): string { return $this->relationship; }
    public function birthDate(): ?DateTimeImmutable { return $this->birthDate; }
}
