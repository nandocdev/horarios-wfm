<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

final readonly class ImportEmployeesDTO
{
    public function __construct(
        public string $storedPath,
        public string $originalFilename,
        public int $createdBy,
        public int $chunkSize = 1000,
    ) {}
}
