<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use Illuminate\Database\Eloquent\Model;

trait Auditable {
    public static function bootAuditable(): void {
        static::created(function (Model $model) {
            AuditLogBridge::log($model, 'created');
        });

        static::updated(function (Model $model) {
            AuditLogBridge::log($model, 'updated');
        });

        static::deleted(function (Model $model) {
            AuditLogBridge::log($model, 'deleted');
        });
    }
}
