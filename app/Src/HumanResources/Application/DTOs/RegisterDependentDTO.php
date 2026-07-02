<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Application\DTOs;

use DateTimeImmutable;

final readonly class RegisterDependentDTO
{
    public function __construct(
        public int $employeeId,
        public string $name,
        public string $relationship,
        public ?DateTimeImmutable $birthDate = null,
    ) {}
}
