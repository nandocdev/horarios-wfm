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
            $before = $model->getOriginal();
            $after = $model->toArray();

            // Calcular solo los campos que realmente cambiaron
            $changed = self::getChangedFields($before, $after);

            self::logChange($model, 'updated', $changed);
        });

        static::deleted(function (Model $model) {
            $before = $model->getOriginal();
            self::logChange($model, 'deleted', $before->toArray() ?? []);
        });
    }

    /**
     * Registra un cambio de estado en el AuditLog.
     *
     * @param  array<string, mixed>  $changedFields  Campos que cambiaron (solo en updated)
     */
    protected static function logChange(Model $model, string $action, array $changedFields): void
    {
        $user = auth()->user();

        AuditLog::create([
            'entity_type' => get_class($model),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'before' => $action !== 'created' ? $changedFields : null,
            'after' => $action === 'created' ? array_merge(
                $model->toArray(),
                ['created_at' => $model->created_at->toIso8601String(),
                    'updated_at' => $model->updated_at->toIso8601String()]
            ) : $model->toArray(),
            'ip_address' => request()->ip(),
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'actor_email' => $user?->email,
        ]);
    }

    /**
     * Determina los campos que realmente cambiaron entre before y after.
     *
     * @return array<string, mixed> Pares clave->valor solo de los campos modificados
     */
    protected static function getChangedFields(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $key => $afterValue) {
            $beforeValue = data_get($before, $key);

            // Si el valor es diferente (considerando null vs no existente)
            if ($beforeValue !== $afterValue) {
                $changed[$key] = $afterValue;
            }
        }

        return $changed;
    }
}
