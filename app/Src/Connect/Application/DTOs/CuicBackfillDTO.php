<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class CuicBackfillDTO
{
    public function __construct(
        public string $dateFrom,
        public string $dateTo,
        public array $reportTypes,
    ) {}
}
