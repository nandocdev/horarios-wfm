<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\Events;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;

final readonly class AuditEntryCreated
{
    public function __construct(
        public AuditLogEntry $entry,
    ) {}
}
