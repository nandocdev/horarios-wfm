<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class SyncCuicFilterDTO
{
    public function __construct(
        public string $reportType,
        public string $dateFrom,
        public string $dateTo,
        public ?int $minutes = null,
    ) {}
}
