<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\ExportAuditLogs;

final readonly class Command
{
    public function __construct(
        public ?string $search = null,
        public ?string $action = null,
        public ?string $entityType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public string $format = 'csv',
    ) {}
}
