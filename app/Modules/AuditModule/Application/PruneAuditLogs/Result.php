<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\PruneAuditLogs;

use DateTimeImmutable;

final readonly class Result
{
    public function __construct(
        public int $affected,
        public DateTimeImmutable $cutoff,
        public bool $dryRun = false,
    ) {}
}
