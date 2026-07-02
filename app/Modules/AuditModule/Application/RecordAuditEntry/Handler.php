<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\RecordAuditEntry;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;
use App\Modules\AuditModule\Domain\Services\AuditLoggingService;

final readonly class Handler
{
    public function __construct(
        private AuditLoggingService $loggingService,
    ) {}

    public function __invoke(Command $command): AuditLogEntry
    {
        return $this->loggingService->log(
            entityType: $command->entityType,
            entityId: $command->entityId,
            action: $command->action,
            before: $command->before,
            after: $command->after,
            userId: $command->userId,
            ipAddress: $command->ipAddress,
        );
    }
}
