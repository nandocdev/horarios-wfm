<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Application\QueryAuditLogs;

use App\Modules\AuditModule\Domain\Repositories\AuditLogRepository;

final readonly class Handler
{
    public function __construct(
        private AuditLogRepository $repository,
    ) {}

    public function __invoke(Query $query): Result
    {
        $filters = array_filter([
            'search' => $query->search,
            'action' => $query->action,
            'entity_type' => $query->entityType,
            'date_from' => $query->dateFrom,
            'date_to' => $query->dateTo,
        ], fn ($v) => $v !== null && $v !== '');

        $items = $this->repository->paginate($filters, $query->perPage, $query->page);
        $total = $this->repository->count($filters);

        return new Result($items, $total, $query->perPage, $query->page);
    }
}
