<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Métricas de Capacity Planning (IND-010..IND-018).
 *
 * Todas las fórmulas son puras: sin I/O, sin Eloquent, sin estado.
 *
 * @see docs/INDICADORES_CORE.md
 */
final class CapacityMetrics
{
    /**
     * IND-010 · Offered Load (Traffic Intensity) en Erlangs.
     *
     * Load = ArrivalRate × AHT / IntervalSeconds
     *
     * @param  float  $arrivalRate  Tasa de llegada por intervalo (contactos/intervalo).
     * @param  float  $ahtSeconds  Tiempo promedio de manejo en segundos.
     * @param  int  $intervalSeconds  Duración del intervalo en segundos.
     * @return float Carga ofrecida en Erlangs.
     */
    public static function offeredLoad(
        float $arrivalRate,
        float $ahtSeconds,
        int $intervalSeconds,
    ): float {
        if ($intervalSeconds <= 0) {
            return 0.0;
        }

        return round($arrivalRate * ($ahtSeconds / $intervalSeconds), 2);
    }

    /**
     * IND-011 / IND-016 · Erlang C — probabilidad de espera.
     *
     * P(wait) = [A^m/m! × m/(m-A)] / [Σ_{i=0}^{m-1} A^i/i! + A^m/m! × m/(m-A)]
     *
     * @param  int  $agents  Número de agentes.
     * @param  float  $load  Carga en Erlangs.
     * @return float Probabilidad de espera.
     */
    public static function erlangC(int $agents, float $load): float
    {
        if ($agents <= 0) {
            return 1.0;
        }

        if ($load <= 0) {
            return 0.0;
        }

        if ($load >= $agents) {
            return 1.0;
        }

        $lastTerm = self::powerFactorial($load, $agents);
        $numerator = $lastTerm * ($agents / ($agents - $load));

        $denominator = 0.0;
        for ($i = 0; $i < $agents; $i++) {
            $denominator += self::powerFactorial($load, $i);
        }
        $denominator += $numerator;

        if ($denominator <= 0) {
            return 1.0;
        }

        return min(1.0, $numerator / $denominator);
    }

    /**
     * IND-011 · Erlang A — aproximación por carga efectiva.
     *
     * Reduce la carga ofrecida por la fracción de contactos que no abandona.
     * Para modelos exactos de Erlang A se requiere librería numérica externa.
     *
     * @param  int  $agents  Número de agentes.
     * @param  float  $load  Carga en Erlangs.
     * @param  float  $abandonmentRate  Fracción de abandono (0..1).
     * @return float Probabilidad de espera.
     */
    public static function erlangA(int $agents, float $load, float $abandonmentRate): float
    {
        $abandonmentRate = min(1.0, max(0.0, $abandonmentRate));
        $effectiveLoad = $load * (1 - $abandonmentRate);

        return self::erlangC($agents, $effectiveLoad);
    }

    /**
     * IND-011 · Agentes requeridos para cumplir un Service Level objetivo.
     *
     * SL = 1 - Pw × e^[-(m-A) × target / AHT]
     *
     * @param  float  $load  Carga en Erlangs.
     * @param  int  $targetSeconds  Tiempo objetivo en segundos.
     * @param  float  $ahtSeconds  Tiempo promedio de manejo en segundos.
     * @param  float  $targetSl  Nivel de servicio objetivo (%)
     * @param  float|null  $abandonmentRate  Fracción de abandono (0..1) para Erlang A.
     * @param  int  $maxAgents  Límite superior de búsqueda.
     * @return int Número mínimo de agentes para cumplir el Service Level objetivo.
     */
    public static function agentsForServiceLevel(
        float $load,
        int $targetSeconds,
        float $ahtSeconds,
        float $targetSl,
        ?float $abandonmentRate = null,
        int $maxAgents = 500,
    ): int {
        $targetFraction = min(100.0, max(0.0, $targetSl)) / 100;
        $startAgents = max(1, (int) ceil($load));

        for ($agents = $startAgents; $agents <= $maxAgents; $agents++) {
            $pw = $abandonmentRate !== null
                ? self::erlangA($agents, $load, $abandonmentRate)
                : self::erlangC($agents, $load);

            $sl = $ahtSeconds > 0
                ? 1 - $pw * exp(-($agents - $load) * $targetSeconds / $ahtSeconds)
                : 1.0;

            if ($sl >= $targetFraction) {
                return $agents;
            }
        }

        return $maxAgents;
    }

    /**
     * IND-012 · Required Staff (Net Staffing).
     *
     * Required = Erlang_Agents / (1 - Shrinkage)
     *
     * @param  float  $erlangAgents  Agentes en Erlangs.
     * @param  float  $shrinkageRate  Shrinkage en porcentaje.
     * @return float Required Staff.
     */
    public static function requiredStaff(float $erlangAgents, float $shrinkageRate): float
    {
        $shrinkageRate = min(1.0, max(0.0, $shrinkageRate));
        if ($shrinkageRate >= 1.0) {
            return 0.0;
        }

        return round($erlangAgents / (1 - $shrinkageRate), 2);
    }

    /**
     * IND-013 · Shrinkage.
     *
     * Shrinkage = 1 - (Productive_Time / Paid_Time) × 100
     */
    public static function shrinkage(float $productiveTime, float $paidTime): float
    {
        if ($paidTime <= 0) {
            return 0.0;
        }

        return round((1 - ($productiveTime / $paidTime)) * 100, 2);
    }

    /**
     * IND-014 · Interval Shrinkage Forecast.
     *
     * Forecast Shrinkage = Planned + Expected Unplanned
     */
    public static function intervalShrinkageForecast(
        float $plannedShrinkage,
        float $expectedUnplannedShrinkage,
    ): float {
        return round($plannedShrinkage + $expectedUnplannedShrinkage, 2);
    }

    /**
     * IND-015 · Interval Occupancy Forecast.
     *
     * Forecast Occupancy = Forecast Load / Forecast Agents × 100
     */
    public static function intervalOccupancyForecast(float $load, float $agents): float
    {
        if ($agents <= 0) {
            return 0.0;
        }

        return round(($load / $agents) * 100, 2);
    }

    /**
     * IND-017 · Expected Wait Time (EWT).
     *
     * EWT = Pw × AHT / (Agents - Load)
     *
     * @param  float|null  $abandonmentRate  Fracción de abandono (0..1) para Erlang A.
     */
    public static function expectedWaitTime(
        int $agents,
        float $load,
        float $ahtSeconds,
        ?float $abandonmentRate = null,
    ): float {
        if ($agents <= $load || $ahtSeconds <= 0) {
            return 0.0;
        }

        $pw = $abandonmentRate !== null
            ? self::erlangA($agents, $load, $abandonmentRate)
            : self::erlangC($agents, $load);

        if ($pw <= 0) {
            return 0.0;
        }

        return round($pw * ($ahtSeconds / ($agents - $load)), 2);
    }

    /**
     * IND-018 · Multi-skill Efficiency.
     *
     * Effective Staff = Σ agent_skill_weight
     *
     * @param  float[]  $skillWeights
     */
    public static function effectiveStaff(array $skillWeights): float
    {
        $effective = 0.0;
        foreach ($skillWeights as $weight) {
            $effective += min(1.0, max(0.0, (float) $weight));
        }

        return round($effective, 2);
    }

    /**
     * A^m / m! calculado de forma incremental para estabilidad numérica.
     */
    private static function powerFactorial(float $load, int $n): float
    {
        if ($n < 0) {
            return 0.0;
        }

        $result = 1.0;
        for ($i = 1; $i <= $n; $i++) {
            $result *= $load / $i;
        }

        return $result;
    }
}
