<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait para logging automático de cambios en modelos.
 *
 * Registra automáticamente cambios en AuditLog cuando se crean, actualizan o eliminan modelos.
 */
trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            AuditLog::log($model, 'created');
        });

        static::updated(function (Model $model) {
            AuditLog::log($model, 'updated');
        });

        static::deleted(function (Model $model) {
            AuditLog::log($model, 'deleted');
        });
    }
}
