<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\AuditLog;
use App\Src\Platform\Infrastructure\Persistence\EloquentAuditLog;
use DateTimeImmutable;

final class AuditLogMapper {
    public static function toDomain(EloquentAuditLog $eloquent): AuditLog {
        return AuditLog::fromDatabase(
            id: $eloquent->id,
            entityType: $eloquent->entity_type,
            entityId: $eloquent->entity_id,
            action: $eloquent->action,
            before: $eloquent->before,
            after: $eloquent->after,
            ipAddress: $eloquent->ip_address,
            userId: $eloquent->user_id,
            createdAt: new DateTimeImmutable($eloquent->created_at),
        );
    }

    public static function toArray(AuditLog $auditLog): array {
        return [
            'id' => $auditLog->id(),
            'entity_type' => $auditLog->entityType(),
            'entity_id' => $auditLog->entityId(),
            'action' => $auditLog->action(),
            'before' => $auditLog->before(),
            'after' => $auditLog->after(),
            'ip_address' => $auditLog->ipAddress(),
            'user_id' => $auditLog->userId(),
            'created_at' => $auditLog->createdAt()->format('Y-m-d H:i:s'),
        ];
    }
}
