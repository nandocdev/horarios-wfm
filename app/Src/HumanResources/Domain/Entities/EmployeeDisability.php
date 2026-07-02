<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\Entities;

final class EmployeeDisability
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $employeeId,
        private readonly int $disabilityTypeId,
        private readonly ?string $notes,
    ) {}

    public static function register(int $employeeId, int $disabilityTypeId, ?string $notes = null): self
    {
        return new self(null, $employeeId, $disabilityTypeId, $notes);
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function disabilityTypeId(): int { return $this->disabilityTypeId; }
    public function notes(): ?string { return $this->notes; }
}
