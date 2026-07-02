<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Application\DTOs;

final readonly class RegisterDisabilityDTO
{
    public function __construct(
        public int $employeeId,
        public int $disabilityTypeId,
        public ?string $notes = null,
        public int $registeredByUserId,
    ) {}
}
