<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\RecordAuditEntry;

final readonly class Command
{
    public function __construct(
        public string $entityType,
        public string|int $entityId,
        public string $action,
        public ?array $before = null,
        public ?array $after = null,
        public ?int $userId = null,
        public ?string $ipAddress = null,
    ) {}
}
