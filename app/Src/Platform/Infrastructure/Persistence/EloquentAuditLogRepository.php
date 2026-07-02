<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Domain\Entities\AuditLog;
use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

final class EloquentAuditLogRepository implements AuditLogRepositoryInterface {
    public function save(AuditLog $auditLog): AuditLog {
        $eloquent = EloquentAuditLog::updateOrCreate(
            ['id' => $auditLog->id()],
            [
                'entity_type' => $auditLog->entityType(),
                'entity_id' => $auditLog->entityId(),
                'action' => $auditLog->action(),
                'before' => $auditLog->before(),
                'after' => $auditLog->after(),
                'ip_address' => $auditLog->ipAddress(),
                'user_id' => $auditLog->userId(),
            ]
        );

        return AuditLog::fromDatabase(
            id: $eloquent->id,
            entityType: $eloquent->entity_type,
            entityId: $eloquent->entity_id,
            action: $eloquent->action,
            before: $eloquent->before,
            after: $eloquent->after,
            ipAddress: $eloquent->ip_address,
            userId: $eloquent->user_id,
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
            ? $eloquent->created_at
            : DateTimeImmutable::createFromMutable($eloquent->created_at),
        );
    }

    public function findById(int $id): ?AuditLog {
        $eloquent = EloquentAuditLog::find($id);

        if (!$eloquent) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function search(array $filters, int $perPage = 25): array {
        $query = EloquentAuditLog::query();
        $query->filter($filters);
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage)->items();
    }

    public function pruneOlderThan(DateTimeImmutable $cutoff, int $chunkSize = 500): int {
        $deleted = 0;

        EloquentAuditLog::where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))
            ->chunkById($chunkSize, function ($logs) use (&$deleted) {
                foreach ($logs as $log) {
                    $log->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    public function countOlderThan(DateTimeImmutable $cutoff): int {
        return EloquentAuditLog::where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))->count();
    }

    private function toDomain(EloquentAuditLog $eloquent): AuditLog {
        return AuditLog::fromDatabase(
            id: $eloquent->id,
            entityType: $eloquent->entity_type,
            entityId: $eloquent->entity_id,
            action: $eloquent->action,
            before: $eloquent->before,
            after: $eloquent->after,
            ipAddress: $eloquent->ip_address,
            userId: $eloquent->user_id,
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
            ? $eloquent->created_at
            : DateTimeImmutable::createFromMutable($eloquent->created_at),
        );
    }
}
