<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Métricas de Cost & Workforce Health (IND-039..IND-040).
 *
 * Todas las fórmulas son puras: sin I/O, sin Eloquent, sin estado.
 *
 * @see docs/INDICADORES_CORE.md
 */
final class CostWorkforceMetrics
{
    /**
     * IND-039 · Cost per Contact (CPC).
     *
     * CPC = Total_Operating_Cost / Handled_Volume
     */
    public static function costPerContact(
        float $totalOperatingCost,
        int $handledVolume,
    ): float {
        if ($handledVolume <= 0) {
            return 0.0;
        }

        return round($totalOperatingCost / $handledVolume, 4);
    }

    /**
     * IND-039 · Cost per Resolution (CPR).
     *
     * CPR = Total_Operating_Cost / Resolved_Volume
     */
    public static function costPerResolution(
        float $totalOperatingCost,
        int $resolvedVolume,
    ): float {
        if ($resolvedVolume <= 0) {
            return 0.0;
        }

        return round($totalOperatingCost / $resolvedVolume, 4);
    }

    /**
     * IND-040 · Attrition / Churn Rate Operativo.
     *
     * Attr = (Agents_Left / Average_Active_Agents) × 100
     */
    public static function attritionRate(
        int $agentsLeft,
        float $averageActiveAgents,
    ): float {
        if ($averageActiveAgents <= 0) {
            return 0.0;
        }

        return round(($agentsLeft / $averageActiveAgents) * 100, 2);
    }
}
