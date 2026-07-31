<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Métricas de Service Quality (IND-034..IND-038).
 *
 * Todas las fórmulas son puras: sin I/O, sin Eloquent, sin estado.
 *
 * @see docs/INDICADORES_CORE.md
 */
final class ServiceQualityMetrics
{
    /**
     * IND-034 · Service Level (SL) — variante of offered.
     *
     * SL = Answered_within_Threshold / Offered × 100
     */
    public static function serviceLevel(
        int $callsWithinThreshold,
        int $totalOfferedCalls,
    ): float {
        if ($totalOfferedCalls <= 0) {
            return 0.0;
        }

        return round(($callsWithinThreshold / $totalOfferedCalls) * 100, 2);
    }

    /**
     * IND-034 · Service Level (SL) — variante menos abandonos dentro del umbral.
     *
     * SL = Answered_within_Threshold / (Offered - Abandoned_within_Threshold) × 100
     */
    public static function serviceLevelExcludingShortAbandons(
        int $callsWithinThreshold,
        int $offered,
        int $abandonedWithinThreshold,
    ): float {
        $denominator = $offered - $abandonedWithinThreshold;
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($callsWithinThreshold / $denominator) * 100, 2);
    }

    /**
     * IND-035 · ASA (Average Speed of Answer).
     *
     * ASA = Total_Wait_Time / Answered_Contacts
     */
    public static function asa(float $totalWaitTime, int $answeredCalls): float
    {
        if ($answeredCalls <= 0) {
            return 0.0;
        }

        return round($totalWaitTime / $answeredCalls, 2);
    }

    /**
     * IND-036 · Abandonment Rate.
     *
     * Abandon = Abandoned / Offered × 100
     */
    public static function abandonmentRate(int $abandoned, int $offered): float
    {
        if ($offered <= 0) {
            return 0.0;
        }

        return round(($abandoned / $offered) * 100, 2);
    }

    /**
     * IND-037 · Average Handle Time (AHT).
     *
     * AHT = (Talk + Hold + ACW) / Handled
     */
    public static function aht(
        float $totalTalkTime,
        float $totalHoldTime,
        float $totalAcwTime,
        int $totalCalls,
    ): float {
        if ($totalCalls <= 0) {
            return 0.0;
        }

        return round(($totalTalkTime + $totalHoldTime + $totalAcwTime) / $totalCalls, 2);
    }

    /**
     * IND-037 · Average Handle Time Components.
     *
     * Devuelve talk, hold, acw y aht en segundos por contacto.
     *
     * @return array<string, float>
     */
    public static function ahtComponents(
        float $totalTalkTime,
        float $totalHoldTime,
        float $totalAcwTime,
        int $totalCalls,
    ): array {
        $aht = self::aht($totalTalkTime, $totalHoldTime, $totalAcwTime, $totalCalls);

        return [
            'talk' => $totalCalls > 0 ? round($totalTalkTime / $totalCalls, 2) : 0.0,
            'hold' => $totalCalls > 0 ? round($totalHoldTime / $totalCalls, 2) : 0.0,
            'acw' => $totalCalls > 0 ? round($totalAcwTime / $totalCalls, 2) : 0.0,
            'aht' => $aht,
        ];
    }

    /**
     * IND-038 · FCR (First Contact Resolution) — auditoría de reincidencia.
     *
     * FCR = 1 - (Unique_Callers_With_Repeat / Total_Unique_Callers) × 100
     */
    public static function fcr(
        int $uniqueCallersWithRepeat,
        int $totalUniqueCallers,
    ): float {
        if ($totalUniqueCallers <= 0) {
            return 0.0;
        }

        $repeatRate = $uniqueCallersWithRepeat / $totalUniqueCallers;

        return round((1 - $repeatRate) * 100, 2);
    }
}
