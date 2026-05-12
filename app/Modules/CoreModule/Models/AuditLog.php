<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para registro de auditoría de cambios en el sistema.
 *
 * Registra todas las operaciones CRUD en modelos auditables
 * para mantener trazabilidad completa.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
        'ip_address',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'entity_id' => 'integer',
    ];

    /**
     * Usuario que realizó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registrar un evento de auditoría.
     */
    public static function log(
        string $entityType,
        int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): self {
        return static::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => $ipAddress ?? request()?->ip(),
        ]);
    }
}
