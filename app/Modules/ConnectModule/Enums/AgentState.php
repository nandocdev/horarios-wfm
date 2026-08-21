<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Enums;

/**
 * Estado explícito del agente en el call center.
 *
 * Define transiciones válidas y está auditable para cumplimiento laboral.
 * Cada cambio de estado debe generar un registro en AgentStateTransition.
 *
 * Reglas de transición para call center:
 * - READY puede ir a: TALKING, NOT_READY, RESERVED, HOLD
 * - TALKING (después de llamada) puede ir a: READY, NOT_READY, HOLD
 * - NOT_READY (break/lunch) puede ir a: READY, TALKING
 * - HOLD puede ir a: READY, TALKING
 * - RESERVED puede ir a: READY, TALKING
 * - OUTBOUND (llamada saliente) puede ir a: READY
 * - LOGOUT/OFFLINE puede ir a: READY (agente vuelve a loguearse)
 * - UNKNOWN puede ir a: READY u OFFLINE
 *
 * Queda PROHIBIDO:
 * - Cualquier transición que no esté en la matriz definida
 * - Transiciones que rompan la lógica de negocio (ej. TALKING -> RESERVED sin haber colgado)
 */
enum AgentState: string
{
    case READY = 'READY';
    case TALKING = 'TALKING';
    case HOLD = 'HOLD';
    case WORK = 'WORK';
    case RESERVED = 'RESERVED';
    case OUTBOUND = 'OUTBOUND';
    case NOT_READY = 'NOT_READY';
    case LOGOUT = 'LOGOUT';
    case OFFLINE = 'OFFLINE';
    case UNKNOWN = 'UNKNOWN';

    /**
     * Estados productivos (tiempo contable para AHT/Occupancy calculations).
     * Estos estados cuentan para el tiempo de ocupación y AHT.
     */
    public const PRODUCTIVE = [
        self::TALKING,
        self::WORK,
        self::RESERVED,
        self::HOLD,
        self::OUTBOUND,
    ];

    /**
     * Estados no productivos (breaks, auxiliar, etc.).
     * Estos estados NO cuentan para el tiempo de ocupación,
     * pero el agente sigue pagado y conectado al sistema.
     */
    public const NON_PRODUCTIVE = [
        self::NOT_READY,
    ];

    /**
     * Estados que requieren re-login para volver a ser productivos.
     * Agentes que estaban LOGOUT/OFFLINE necesitan autenticarse nuevamente
     * para volver al estado READY.
     */
    public const RELOGIN_REQUIRED = [
        self::LOGOUT,
        self::OFFLINE,
    ];

    /**
     * Determina si el estado es productivo para cálculos de ocupación.
     */
    public function isProductive(): bool
    {
        return in_array($this->value, self::PRODUCTIVE, true);
    }

    /**
     * Determina si el estado es no productivo.
     */
    public function isNonProductive(): bool
    {
        return in_array($this->value, self::NON_PRODUCTIVE, true);
    }

    /**
     * Determina si el estado requiere re-login para volver a READY.
     */
    public function requiresRelogin(): bool
    {
        return in_array($this->value, self::RELOGIN_REQUIRED, true);
    }

    /**
     * Estados a los que se puede transitar desde READY.
     */
    public function fromReady(): array
    {
        return [
            self::TALKING->value,
            self::NOT_READY->value,
            self::RESERVED->value,
            self::HOLD->value,
        ];
    }

    /**
     * Estados a los que se puede transitar desde TALKING (después de una llamada).
     */
    public function fromTalking(): array
    {
        return [
            self::READY->value,
            self::NOT_READY->value,
            self::HOLD->value,
        ];
    }

    /**
     * Estados a los que se puede transitar desde NOT_READY.
     */
    public function fromNotReady(): array
    {
        return [
            self::READY->value,
            self::TALKING->value,
        ];
    }

    /**
     * Obtiene todos los nombres de estado para validación UI.
     */
    public static function allNames(): array
    {
        return [
            'READY' => 'Listo',
            'TALKING' => 'Hablando',
            'HOLD' => 'Música en espera',
            'WORK' => 'Post-llamada (ACW)',
            'RESERVED' => 'Reservado',
            'OUTBOUND' => 'Fuera de servicio (Outbound)',
            'NOT_READY' => 'No listo',
            'LOGOUT' => 'Desconectado',
            'OFFLINE' => 'Fuera de línea',
            'UNKNOWN' => 'Desconocido',
        ];
    }

    /**
     * Obtiene el color CSS para la visualización en dashboards.
     */
    public function color(): string
    {
        return match ($this) {
            self::READY => 'bg-wfm-success',
            self::TALKING => 'bg-wfm-info',
            self::HOLD => 'bg-warning',
            self::WORK => 'bg-purple-500',
            self::RESERVED => 'bg-cyan-500',
            self::OUTBOUND => 'bg-orange-400',
            self::NOT_READY => 'bg-amber-500',
            self::LOGOUT => 'bg-wfm-danger',
            self::OFFLINE => 'bg-wfm-danger',
            self::UNKNOWN => 'bg-gray-500',
        };
    }

    /**
     * Obtiene la descripción legible para dashboards.
     */
    public function label(): string
    {
        return match ($this) {
            self::READY => 'Listo para llamadas',
            self::TALKING => 'En llamada',
            self::HOLD => 'En espera',
            self::WORK => 'Trabajo post-llamada',
            self::RESERVED => 'Reservado para otra tarea',
            self::OUTBOUND => 'Llamada saliente',
            self::NOT_READY => 'No disponible',
            self::LOGOUT => 'Desconectado del sistema',
            self::OFFLINE => 'Fuera de línea',
            self::UNKNOWN => 'Estado desconocido',
        };
    }

    /**
     * Validates if a transition FROM $from TO $to is valid.
     *
     * Reglas de transición para call center:
     * - READY puede ir a: TALKING, NOT_READY, RESERVED, HOLD
     * - TALKING (después de llamada) puede ir a: READY, NOT_READY, HOLD
     * - NOT_READY (break/lunch) puede ir a: READY, TALKING
     * - HOLD puede ir a: READY, TALKING
     * - RESERVED puede ir a: READY, TALKING
     * - OUTBOUND (llamada saliente) puede ir a: READY
     * - LOGOUT/OFFLINE puede ir a: READY (agente vuelve a loguearse)
     * - UNKNOWN puede ir a: READY u OFFLINE
     *
     * Queda PROHIBIDO:
     * - Cualquier transición que no esté en la matriz definida
     * - Transiciones que rompan la lógica de negocio (ej. TALKING -> RESERVED sin haber colgado)
     */
    public static function isValidTransition(string $from, string $to): bool
    {
        $fromState = self::tryFrom($from);
        $toState = self::tryFrom($to);

        if ($fromState === null || $toState === null) {
            return false;
        }

        // Valid transition matrix
        $transitions = [
            self::READY->value => [self::TALKING->value, self::NOT_READY->value, self::RESERVED->value, self::HOLD->value],
            self::TALKING->value => [self::READY->value, self::NOT_READY->value, self::HOLD->value],
            self::NOT_READY->value => [self::READY->value, self::TALKING->value],
            self::HOLD->value => [self::READY->value, self::TALKING->value],
            self::RESERVED->value => [self::READY->value, self::TALKING->value],
            self::OUTBOUND->value => [self::READY->value],
            self::LOGOUT->value => [self::READY->value, self::OFFLINE->value],
            self::OFFLINE->value => [self::READY->value, self::OFFLINE->value],
            self::UNKNOWN->value => [self::READY->value, self::OFFLINE->value],
        ];

        return !empty($transitions[$fromState->value] ?? null)
            && in_array($toState->value, $transitions[$fromState->value], true);
    }

    private static function tryFrom(?string $value): ?self
    {
        return self::cases(
            fn ($case) => $case->value === $value ?? false
        )->first() ?? null;
    }
}