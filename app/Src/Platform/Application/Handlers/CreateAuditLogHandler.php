<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\CreateAuditLogDTO;
use App\Src\Platform\Domain\Entities\AuditLog;
use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;

final class CreateAuditLogHandler {
    public function __construct(
        private AuditLogRepositoryInterface $repository,
    ) {
    }

    public function handle(CreateAuditLogDTO $dto): AuditLog {
        $auditLog = AuditLog::create(
            entityType: $dto->entityType,
            entityId: $dto->entityId,
            action: $dto->action,
            before: $dto->before,
            after: $dto->after,
            ipAddress: $dto->ipAddress,
            userId: $dto->userId,
        );

        return $this->repository->save($auditLog);
    }
}
