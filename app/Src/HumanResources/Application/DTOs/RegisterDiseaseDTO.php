<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Application\DTOs;

final readonly class RegisterDiseaseDTO
{
    public function __construct(
        public int $employeeId,
        public int $diseaseTypeId,
        public string $notes,
        public int $registeredByUserId,
    ) {}
}
