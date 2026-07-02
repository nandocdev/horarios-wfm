<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\QueryAuditLogs;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;

final readonly class Result
{
    /** @param AuditLogEntry[] $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $page,
    ) {}

    public function lastPage(): int
    {
        return (int) ceil($this->total / max($this->perPage, 1));
    }
}
