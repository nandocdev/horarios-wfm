<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\AuditLogExportDTO;
use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;
use App\Src\Platform\Infrastructure\Persistence\EloquentAuditLog;

final readonly class ExportAuditLogsHandler {
    public function __construct(
        private AuditLogRepositoryInterface $repository,
    ) {}

    public function execute(AuditLogExportDTO $dto): array {
        $filters = $dto->toFilterArray();
        $domainLogs = $this->repository->search($filters, 0);

        return array_map(function ($log) {
            if ($log instanceof EloquentAuditLog) {
                return [
                    'id' => $log->id,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'action' => $log->action,
                    'before' => $log->before,
                    'after' => $log->after,
                    'user_name' => $log->relationLoaded('user') && $log->user ? $log->user->name : null,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toDateTimeString(),
                ];
            }

            return (array) $log;
        }, $domainLogs);
    }
}
