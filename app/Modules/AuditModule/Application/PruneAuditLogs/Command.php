<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\PruneAuditLogs;

final readonly class Command
{
    public function __construct(
        public int $days = 90,
        public int $chunkSize = 500,
        public bool $dryRun = false,
    ) {}
}
