<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Domain\Entities\AuditLog;
use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

final class AuditLogBridge {
    private static ?AuditLogRepositoryInterface $repository = null;

    public static function log(Model $model, string $action): void {
        $before = null;
        $after = null;

        if ($action === 'created') {
            $after = $model->toArray();
        } elseif ($action === 'updated') {
            $before = $model->getOriginal();
            $after = $model->toArray();
        } elseif ($action === 'deleted') {
            $before = $model->getOriginal();
        }

        $auditLog = AuditLog::create(
            entityType: get_class($model),
            entityId: $model->getKey(),
            action: $action,
            before: $before,
            after: $after,
            ipAddress: request()?->ip(),
            userId: auth()->id(),
        );

        self::repository()->save($auditLog);
    }

    public static function logCustom(
        string $entityType,
        int|string|null $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $ipAddress = null,
        ?int $userId = null,
    ): void {
        $auditLog = AuditLog::create(
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            before: $before,
            after: $after,
            ipAddress: $ipAddress ?? request()?->ip(),
            userId: $userId ?? auth()->id(),
        );

        self::repository()->save($auditLog);
    }

    private static function repository(): AuditLogRepositoryInterface {
        if (self::$repository === null) {
            self::$repository = app(AuditLogRepositoryInterface::class);
        }

        return self::$repository;
    }
}
