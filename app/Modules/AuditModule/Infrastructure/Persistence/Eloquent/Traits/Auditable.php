<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits;

use App\Modules\AuditModule\Application\RecordAuditEntry\Command;
use App\Modules\AuditModule\Application\RecordAuditEntry\Handler;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::recordAuditEntry($model, 'created');
        });

        static::updated(function (Model $model) {
            self::recordAuditEntry($model, 'updated');
        });

        static::deleted(function (Model $model) {
            self::recordAuditEntry($model, 'deleted');
        });
    }

    protected static function recordAuditEntry(Model $model, string $action): void
    {
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

        $command = new Command(
            entityType: get_class($model),
            entityId: $model->getKey(),
            action: $action,
            before: $before,
            after: $after,
            userId: auth()->id(),
            ipAddress: request()?->ip(),
        );

        app(Handler::class)($command);
    }
}
