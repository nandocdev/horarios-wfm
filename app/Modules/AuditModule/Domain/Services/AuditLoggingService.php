<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Domain\Services;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;
use App\Modules\AuditModule\Domain\Events\AuditEntryCreated;
use App\Modules\AuditModule\Domain\Repositories\AuditLogRepository;
use Illuminate\Contracts\Events\Dispatcher;

final class AuditLoggingService
{
    public function __construct(
        private AuditLogRepository $repository,
        private ?Dispatcher $laravelEvents = null,
    ) {}

    public function log(
        string $entityType,
        string|int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
        ?string $ipAddress = null,
    ): AuditLogEntry {
        $entry = AuditLogEntry::record(
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            before: $before,
            after: $after,
            userId: $userId,
            ipAddress: $ipAddress,
        );

        $this->repository->save($entry);

        foreach ($entry->releaseEvents() as $event) {
            if ($event instanceof AuditEntryCreated && $this->laravelEvents) {
                $this->laravelEvents->dispatch($event);
            }
        }

        return $entry;
    }
}
