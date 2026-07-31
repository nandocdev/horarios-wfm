<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Fachada de compatibilidad hacia atrás para fórmulas de métricas.
 *
 * Las implementaciones canónicas ahora viven en clases especializadas por dominio:
 * - ForecastMetrics
 * - CapacityMetrics
 * - SchedulingMetrics
 * - RealtimeMetrics
 * - ServiceQualityMetrics
 * - CostWorkforceMetrics
 *
 * @deprecated Preferir las clases especializadas para nuevo código.
 * @see docs/INDICADORES_CORE.md
 */
final class MetricFormulas
{
    /**
     * Calcula el porcentaje de productividad.
     *
     * @deprecated Use RealtimeMetrics::productivity().
     */
    public static function productivity(float $productiveMinutes, float $connectedMinutes): float
    {
        return RealtimeMetrics::productivity($productiveMinutes, $connectedMinutes);
    }

    /**
     * Calcula el porcentaje de utilización.
     *
     * @deprecated Use RealtimeMetrics::utilization().
     */
    public static function utilization(float $productiveMinutes, float $baseMinutes): float
    {
        return RealtimeMetrics::utilization($productiveMinutes, $baseMinutes);
    }

    /**
     * Calcula el tiempo base (denominador) para la utilización.
     *
     * @deprecated Use RealtimeMetrics::utilizationDenominator().
     */
    public static function utilizationDenominator(
        int $scheduledMinutes,
        bool $isToday,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null,
    ): int {
        return RealtimeMetrics::utilizationDenominator($scheduledMinutes, $isToday, $startTime, $endTime);
    }

    /**
     * Determina si una marca de tiempo constituye una tardanza.
     *
     * @deprecated Use RealtimeMetrics::checkLate().
     */
    public static function checkLate(
        string|\DateTimeInterface $scheduledEntry,
        string|\DateTimeInterface $actualEntry,
        int $graceMinutes = 5,
    ): bool {
        return RealtimeMetrics::checkLate($scheduledEntry, $actualEntry, $graceMinutes);
    }

    /**
     * Calcula el AHT (Talk + ACW).
     *
     * Mantenido por compatibilidad con callers que no desagregan Hold.
     * El catálogo canónico usa Talk + Hold + ACW (ServiceQualityMetrics::aht).
     *
     * @deprecated Use ServiceQualityMetrics::aht() con los 3 componentes.
     */
    public static function aht(float $totalTalkTime, float $totalWorkTime, int $totalCalls): float
    {
        return ServiceQualityMetrics::aht($totalTalkTime, 0.0, $totalWorkTime, $totalCalls);
    }

    /**
     * Convierte segundos a minutos con precisión configurable.
     */
    public static function secondsToMinutes(int $seconds, int $precision = 1): float
    {
        return round($seconds / 60, $precision);
    }

    /**
     * Formatea una duración en segundos al formato HH:MM:SS.
     */
    public static function formatDuration(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    /**
     * Verifica la adherencia de un estado real frente a un tipo esperado.
     *
     * @deprecated Use RealtimeMetrics::checkAdherence().
     */
    public static function checkAdherence(?string $realState, ?string $expectedType): bool
    {
        return RealtimeMetrics::checkAdherence($realState, $expectedType);
    }

    /**
     * Calcula la Cobertura Operativa (presencia: Available / Scheduled).
     *
     * No confundir con SchedulingMetrics::coverage(), que usa Required/Scheduled.
     *
     * @deprecated Use RealtimeMetrics::availabilityRatio() o SchedulingMetrics::coverage().
     */
    public static function coverageRate(int $availableAgents, int $scheduledAgents): float
    {
        if ($scheduledAgents <= 0) {
            return 0.0;
        }

        return round(($availableAgents / $scheduledAgents) * 100, 1);
    }

    /**
     * Calcula la Tasa de Ausentismo.
     *
     * @deprecated Use CapacityMetrics::shrinkage() o RealtimeMetrics::conformance() según caso.
     */
    public static function absenteeismRate(float $absentMinutes, float $scheduledProductiveMinutes): float
    {
        if ($scheduledProductiveMinutes <= 0) {
            return 0.0;
        }

        return round(($absentMinutes / $scheduledProductiveMinutes) * 100, 1);
    }

    /**
     * Calcula la cantidad de personal ausente (Headcount).
     */
    public static function absentPersonnel(int $scheduled, int $actual): int
    {
        return max(0, $scheduled - $actual);
    }

    /**
     * Calcula la Ocupación (Occupancy).
     *
     * @deprecated Use RealtimeMetrics::occupancy().
     */
    public static function occupancy(
        float $talkTime,
        float $holdTime,
        float $workTime,
        float $totalLoggedTime,
        float $auxTime,
    ): float {
        return RealtimeMetrics::occupancy($talkTime, $holdTime, $workTime, $totalLoggedTime, $auxTime);
    }

    /**
     * Calcula el Conformance.
     *
     * @deprecated Use RealtimeMetrics::conformance().
     */
    public static function conformance(float $actualWorkedMinutes, float $scheduledWorkedMinutes): float
    {
        return RealtimeMetrics::conformance($actualWorkedMinutes, $scheduledWorkedMinutes);
    }

    /**
     * Calcula el ASA (Average Speed of Answer).
     *
     * @deprecated Use ServiceQualityMetrics::asa().
     */
    public static function asa(float $totalQueueWaitTime, int $answeredCalls): float
    {
        return ServiceQualityMetrics::asa($totalQueueWaitTime, $answeredCalls);
    }

    /**
     * Calcula el Service Level (Nivel de Servicio).
     *
     * @deprecated Use ServiceQualityMetrics::serviceLevel().
     */
    public static function serviceLevel(int $callsWithinThreshold, int $totalOfferedCalls): float
    {
        return ServiceQualityMetrics::serviceLevel($callsWithinThreshold, $totalOfferedCalls);
    }
}
