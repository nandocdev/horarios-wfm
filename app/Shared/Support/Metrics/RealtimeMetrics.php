<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Métricas de Real-Time Operations (IND-023..IND-033).
 *
 * Todas las fórmulas son puras: sin I/O, sin Eloquent, sin estado.
 *
 * @see docs/INDICADORES_CORE.md
 */
final class RealtimeMetrics
{
    /**
     * IND-023 · Occupancy (Ocupación).
     *
     * Occupancy = (Talk + Hold + ACW) / (Logged - Aux) × 100
     */
    public static function occupancy(
        float $talkTime,
        float $holdTime,
        float $acwTime,
        float $totalLoggedTime,
        float $auxTime,
    ): float {
        $denominator = $totalLoggedTime - $auxTime;
        if ($denominator <= 0) {
            return 0.0;
        }

        return round((($talkTime + $holdTime + $acwTime) / $denominator) * 100, 2);
    }

    /**
     * Productividad (usado en dashboards históricos).
     *
     * Productivity = Productive / Connected × 100
     */
    public static function productivity(float $productiveMinutes, float $connectedMinutes): float
    {
        if ($connectedMinutes <= 0) {
            return 0.0;
        }

        return round(($productiveMinutes / $connectedMinutes) * 100, 2);
    }

    /**
     * IND-024 · Utilization (Utilización).
     *
     * Utilization = Productive / Paid × 100
     */
    public static function utilization(float $productiveMinutes, float $baseMinutes): float
    {
        if ($baseMinutes <= 0) {
            return $productiveMinutes > 0 ? 100.0 : 0.0;
        }

        return round(min(100.0, ($productiveMinutes / $baseMinutes) * 100), 2);
    }

    /**
     * Denominador para utilization cuando el turno está en curso.
     */
    public static function utilizationDenominator(
        int $scheduledMinutes,
        bool $isToday,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null,
    ): int {
        if (! $isToday || $startTime === null || $endTime === null) {
            return max(1, $scheduledMinutes);
        }

        $now = new \DateTimeImmutable;

        if ($now < $startTime) {
            return 0;
        }

        if ($now > $endTime) {
            return max(1, $scheduledMinutes);
        }

        $elapsed = (int) floor(($now->getTimestamp() - $startTime->getTimestamp()) / 60);

        return max(1, min($scheduledMinutes, $elapsed));
    }

    /**
     * IND-025 · Adherence (RTA) — tasa.
     *
     * Adherence = Time_in_Scheduled_State / Scheduled_Time × 100
     */
    public static function adherenceRate(
        float $timeInScheduledState,
        float $scheduledTime,
    ): float {
        if ($scheduledTime <= 0) {
            return 0.0;
        }

        return round(min(100.0, ($timeInScheduledState / $scheduledTime) * 100), 2);
    }

    /**
     * IND-025 · Adherence (RTA) — verificación por estado.
     */
    public static function checkAdherence(?string $realState, ?string $expectedType): bool
    {
        $real = strtoupper($realState ?? 'OFFLINE');
        $isLogoutOrOffline = in_array($real, ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN'], true);
        $productiveStates = ['READY', 'TALKING', 'WORK', 'RESERVED', 'HOLD', 'OUTBOUND'];

        if ($isLogoutOrOffline) {
            return $expectedType === 'OFF';
        }

        if ($expectedType === 'OFF') {
            return false;
        }

        if ($expectedType === 'SHIFT') {
            return in_array($real, $productiveStates, true);
        }

        if ($expectedType === 'INTRADAY' || $expectedType === 'EXCEPTION') {
            return $real === 'NOT_READY';
        }

        return false;
    }

    /**
     * IND-026 · Conformance.
     *
     * Conformance = Time_Worked_in_Schedule_Window / Scheduled_Time × 100
     */
    public static function conformance(
        float $actualWorkedMinutes,
        float $scheduledWorkedMinutes,
    ): float {
        if ($scheduledWorkedMinutes <= 0) {
            return 0.0;
        }

        return round(($actualWorkedMinutes / $scheduledWorkedMinutes) * 100, 2);
    }

    /**
     * IND-027 · Login Compliance — verificación binaria.
     */
    public static function checkLate(
        string|\DateTimeInterface $scheduledEntry,
        string|\DateTimeInterface $actualEntry,
        int $graceMinutes = 5,
    ): bool {
        $scheduled = $scheduledEntry instanceof \DateTimeInterface
            ? $scheduledEntry
            : new \DateTimeImmutable($scheduledEntry);

        $actual = $actualEntry instanceof \DateTimeInterface
            ? $actualEntry
            : new \DateTimeImmutable($actualEntry);

        $diffMinutes = ($actual->getTimestamp() - $scheduled->getTimestamp()) / 60;

        return $diffMinutes > $graceMinutes;
    }

    /**
     * IND-027 · Login Compliance — desviación en segundos.
     */
    public static function loginComplianceSeconds(
        string|\DateTimeInterface $scheduledEntry,
        string|\DateTimeInterface $actualEntry,
    ): int {
        $scheduled = $scheduledEntry instanceof \DateTimeInterface
            ? $scheduledEntry
            : new \DateTimeImmutable($scheduledEntry);

        $actual = $actualEntry instanceof \DateTimeInterface
            ? $actualEntry
            : new \DateTimeImmutable($actualEntry);

        return $actual->getTimestamp() - $scheduled->getTimestamp();
    }

    /**
     * IND-028 · Net Staffing Position (Over/Under).
     *
     * Net = Actual_Staffed_Productive - Required
     */
    public static function netStaffingPosition(
        float $actualProductive,
        float $required,
    ): float {
        return round($actualProductive - $required, 2);
    }

    /**
     * IND-029 · Interval Service Variance.
     *
     * Service Variance = SL_Actual - SL_Target
     */
    public static function serviceVariance(float $slActual, float $slTarget): float
    {
        return round($slActual - $slTarget, 2);
    }

    /**
     * IND-030 · Queue Backlog.
     *
     * Backlog = Open Contacts - (Active Agents × Capacity_per_Agent)
     */
    public static function queueBacklog(
        int $openContacts,
        int $activeAgents,
        float $capacityPerAgent,
    ): float {
        $capacity = $activeAgents * $capacityPerAgent;

        return round(max(0.0, $openContacts - $capacity), 2);
    }

    /**
     * IND-031 · Service Deficit (Missing Staff).
     *
     * Missing Staff = Required - Actual_Staffed_Productive
     */
    public static function serviceDeficit(float $required, float $actualProductive): float
    {
        return round(max(0.0, $required - $actualProductive), 2);
    }

    /**
     * IND-032 · Agent Availability Ratio.
     *
     * Availability Ratio = Available / Logged × 100
     */
    public static function availabilityRatio(int $available, int $logged): float
    {
        if ($logged <= 0) {
            return 0.0;
        }

        return round(($available / $logged) * 100, 2);
    }

    /**
     * IND-033 · Occupancy Ceiling.
     *
     * true si Occupancy > Ceiling.
     */
    public static function occupancyCeilingExceeded(
        float $occupancy,
        float $ceiling = 85.0,
    ): bool {
        return $occupancy > $ceiling;
    }
}
