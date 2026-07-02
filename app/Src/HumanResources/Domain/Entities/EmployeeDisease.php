<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\Entities;

use App\Src\HumanResources\Domain\ValueObjects\MedicalNotes;

final class EmployeeDisease
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $employeeId,
        private readonly int $diseaseTypeId,
        private readonly MedicalNotes $notes,
    ) {}

    public static function register(int $employeeId, int $diseaseTypeId, MedicalNotes $notes): self
    {
        return new self(null, $employeeId, $diseaseTypeId, $notes);
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function diseaseTypeId(): int { return $this->diseaseTypeId; }
    public function notes(): MedicalNotes { return $this->notes; }
}
