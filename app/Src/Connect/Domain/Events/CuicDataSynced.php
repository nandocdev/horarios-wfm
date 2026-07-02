<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Events;

final class CuicDataSynced
{
    public function __construct(
        public readonly string $reportType,
        public readonly array $summary,
    ) {}
}
