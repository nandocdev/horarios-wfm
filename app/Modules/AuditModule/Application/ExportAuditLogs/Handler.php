<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\ExportAuditLogs;

use App\Modules\AuditModule\Domain\Aggregates\AuditLogEntry;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogEloquentRepository;

final readonly class Handler
{
    public function __construct(
        private AuditLogEloquentRepository $repository,
    ) {}

    /** @return AuditLogEntry[] */
    public function __invoke(Command $command): array
    {
        $filters = array_filter([
            'search' => $command->search,
            'action' => $command->action,
            'entity_type' => $command->entityType,
            'date_from' => $command->dateFrom,
            'date_to' => $command->dateTo,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->repository->allMatching($filters);
    }
}
