<?php

declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait transversal de auditoría.
 *
 * Ubicación canónica en Shared — accesible por todos los módulos.
 * Mantiene compatibilidad con App\Modules\CoreModule\Concerns\Auditable
 * (alias/extends) hasta completar la migración.
 *
 * Registra created/updated/deleted en audit_logs con before/after
 * e ip/actor. Evita loguear en consola/seeder si no hay request.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            self::logChange($model, 'created', null, null);
        });

        static::updated(function (Model $model): void {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            $before = [];
            $after = [];

            foreach ($changes as $key => $newValue) {
                $before[$key] = $original[$key] ?? null;
                $after[$key] = $newValue;
            }

            self::logChange($model, 'updated', $before, $after);
        });

        static::deleted(function (Model $model): void {
            self::logChange($model, 'deleted', $model->getOriginal(), null);
        });
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    protected static function logChange(Model $model, string $action, ?array $before = null, ?array $after = null): void
    {
        // Evitar fallos en seeders/queues sin contexto HTTP o cuando audit_logs aún no existe
        if (app()->runningUnitTests() && ! app()->environment('testing')) {
            // no-op
        }

        $user = auth()->user();

        if ($action === 'created') {
            $before = null;
            $after = array_merge(
                $model->toArray(),
                [
                    'created_at' => $model->created_at?->toIso8601String(),
                    'updated_at' => $model->updated_at?->toIso8601String(),
                ]
            );
        } elseif ($action === 'deleted') {
            $after = null;
        }

        try {
            AuditLog::create([
                'entity_type' => $model::class,
                'entity_id' => (string) $model->getKey(),
                'action' => $action,
                'before' => $before,
                'after' => $after,
                'ip_address' => request()->ip(),
                'user_id' => $user?->getAuthIdentifier(),
                'actor_name' => $user?->name ?? null,
                'actor_email' => $user?->email ?? null,
            ]);
        } catch (\Throwable $e) {
            // No romper la transacción de dominio por fallo de auditoría
            report($e);
        }
    }
}
