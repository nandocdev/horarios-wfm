<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\QueryAuditLogs;

final readonly class Query
{
    public function __construct(
        public ?string $search = null,
        public ?string $action = null,
        public ?string $entityType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $perPage = 20,
        public int $page = 1,
    ) {}
}
