<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Métricas de Scheduling (IND-019..IND-022).
 *
 * Todas las fórmulas son puras: sin I/O, sin Eloquent, sin estado.
 *
 * @see docs/INDICADORES_CORE.md
 */
final class SchedulingMetrics
{
    /**
     * IND-019 · Schedule Efficiency / Coverage.
     *
     * Coverage = min(Scheduled, Required) / Required × 100
     */
    public static function coverage(float $scheduled, float $required): float
    {
        if ($required <= 0) {
            return 0.0;
        }

        return round(min($scheduled, $required) / $required * 100, 2);
    }

    /**
     * IND-019 · Schedule Efficiency / Coverage (delta alternativa).
     *
     * Efficiency = 1 - |Scheduled - Required| / Required × 100
     */
    public static function scheduleEfficiencyDelta(float $scheduled, float $required): float
    {
        if ($required <= 0) {
            return 0.0;
        }

        return round((1 - (abs($scheduled - $required) / $required)) * 100, 2);
    }

    /**
     * IND-020 · Staffing Efficiency.
     *
     * Efficiency = Required / Scheduled
     */
    public static function staffingEfficiency(float $required, float $scheduled): float
    {
        if ($scheduled <= 0) {
            return 0.0;
        }

        return round($required / $scheduled, 4);
    }

    /**
     * IND-021 · Schedule Fit Score.
     *
     * Fit Score = Σ |Required_i - Scheduled_i|
     *
     * @param  float[]  $required
     * @param  float[]  $scheduled
     */
    public static function scheduleFitScore(
        array $required,
        array $scheduled,
        bool $normalized = false,
    ): float {
        if (count($required) !== count($scheduled) || empty($required)) {
            return 0.0;
        }

        $sum = 0.0;
        $totalRequired = 0.0;
        foreach ($required as $i => $r) {
            $s = $scheduled[$i] ?? 0.0;
            $sum += abs((float) $r - (float) $s);
            $totalRequired += abs((float) $r);
        }

        if ($normalized && $totalRequired > 0) {
            return round($sum / $totalRequired, 4);
        }

        return round($sum, 2);
    }

    /**
     * IND-022 · Schedule Compliance.
     *
     * Checklist binario: Publicado, Aceptado, Sin cambios no autorizados,
     * Dentro de ventana de publicación.
     */
    public static function scheduleComplianceScore(
        bool $published,
        bool $accepted,
        bool $noUnauthorizedChanges,
        bool $withinWindow,
    ): float {
        $checks = [$published, $accepted, $noUnauthorizedChanges, $withinWindow];
        $passed = count(array_filter($checks));

        return round($passed / count($checks) * 100, 2);
    }
}
