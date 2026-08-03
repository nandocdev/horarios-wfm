<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Enums;

/**
 * Disposiciones de contacto Cisco UCCX.
 *
 * Mapeo del campo contact_disposition en call_records.
 * Fuente: documentación UCCX / SyncCuicDataAction.
 */
enum ContactDisposition: int
{
    case Abandoned = 1;
    case Handled = 2;
    case Aborted = 4;
    case Cleansed = 99;

    /**
     * IDs que se consideran "no atendidas" para métricas de abandono.
     * Incluye abandoned (1), aborted (4) y dispositiones adicionales
     * reportadas por CUIC (13).
     */
    private const ABANDONED_IDS = [1, 4, 13];

    /**
     * Retorna los IDs de disposiciones que cuentan como abandono,
     * formateados para uso directo en SQL IN (...).
     */
    public static function abandonedIdsSql(): string
    {
        return implode(', ', self::ABANDONED_IDS);
    }

    /**
     * Determina si un valor de disposition se considera abandonado.
     */
    public static function isAbandoned(int $value): bool
    {
        return in_array($value, self::ABANDONED_IDS, true);
    }

    public static function statusFor(int $value): string
    {
        return match (true) {
            $value === self::Abandoned->value => 'abandoned',
            $value === self::Handled->value => 'closed',
            $value === self::Aborted->value => 'aborted',
            $value >= 5 && $value <= 98 => 'rejected',
            $value === self::Cleansed->value => 'cleansed',
            default => 'pending_operator',
        };
    }
}
