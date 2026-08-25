<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait para logging automático de cambios en modelos del CoreModule.
 *
 * Registra automáticamente cambios en AuditLog cuando se crean, actualizan o
 * eliminan modelos. Captura datos antes/después y dirección IP del request.
 *
 * NOTA: Este trait debe ser aplicado a los modelos que requieren auditoría.
 * Cuando se usa, asegúrese de llamar a `bootAuditable()` en el boot() del
 * ServiceProvider del módulo correspondiente.
 */
trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     *
     * @hook Model::boot
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::logChange($model, 'created', []);
        });

        static::updated(function (Model $model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            // before contiene los valores originales de los campos modificados;
            // after contiene los nuevos valores de esos mismos campos.
            $before = [];
            $after = [];

            foreach ($changes as $key => $newValue) {
                $before[$key] = $original[$key] ?? null;
                $after[$key] = $newValue;
            }

            self::logChange($model, 'updated', $before, $after);
        });

        static::deleted(function (Model $model) {
            self::logChange($model, 'deleted', $model->getOriginal(), null);
        });
    }

    /**
     * Registra un cambio de estado en el AuditLog.
     *
     * @param  array<string, mixed>|null  $before  Valores originales (updated/deleted)
     * @param  array<string, mixed>|null  $after  Valores nuevos (created/updated)
     */
    protected static function logChange(Model $model, string $action, ?array $before = null, ?array $after = null): void
    {
        $user = auth()->user();

        if ($action === 'created') {
            $before = null;
            $after = array_merge(
                $model->toArray(),
                ['created_at' => $model->created_at->toIso8601String(),
                    'updated_at' => $model->updated_at->toIso8601String()]
            );
        } elseif ($action === 'deleted') {
            $after = null;
        }

        AuditLog::create([
            'entity_type' => get_class($model),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'actor_email' => $user?->email,
        ]);
    }
}
