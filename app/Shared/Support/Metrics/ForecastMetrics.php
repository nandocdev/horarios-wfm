<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Métricas de Forecast & Modelos (IND-001..IND-009).
 *
 * Todas las fórmulas son puras: sin I/O, sin Eloquent, sin estado.
 *
 * @see docs/INDICADORES_CORE.md
 */
final class ForecastMetrics
{
    /**
     * IND-001 · Forecast de Volumen (Contact Volume).
     *
     * F_t = base × seasonality × event_factor × trend
     */
    public static function volumeForecastFactor(
        float $base,
        float $seasonality,
        float $eventFactor,
        float $trend,
    ): float {
        return round($base * $seasonality * $eventFactor * $trend, 2);
    }

    /**
     * IND-003 · Intra-day Variance / Forecast Accuracy — MAPE.
     *
     * MAPE = |Actual - Forecast| / Actual × 100
     */
    public static function mape(float $actual, float $forecast): float
    {
        if ($actual <= 0) {
            return 0.0;
        }

        return round(abs($actual - $forecast) / $actual * 100, 2);
    }

    /**
     * IND-004 · Forecast Bias.
     *
     * Bias = (Forecast - Actual) / Actual × 100
     */
    public static function bias(float $actual, float $forecast): float
    {
        if ($actual <= 0) {
            return 0.0;
        }

        return round(($forecast - $actual) / $actual * 100, 2);
    }

    /**
     * IND-005 · WAPE (Weighted Absolute Percentage Error).
     *
     * WAPE = Σ|Error| / ΣActual × 100
     *
     * @param  float[]  $actuals
     * @param  float[]  $forecasts
     */
    public static function wape(array $actuals, array $forecasts): float
    {
        if (empty($actuals)) {
            return 0.0;
        }

        $sumAbsError = 0.0;
        $sumActual = 0.0;

        foreach ($actuals as $i => $actual) {
            $forecast = $forecasts[$i] ?? 0.0;
            $sumAbsError += abs((float) $actual - (float) $forecast);
            $sumActual += abs((float) $actual);
        }

        if ($sumActual <= 0) {
            return 0.0;
        }

        return round($sumAbsError / $sumActual * 100, 2);
    }

    /**
     * IND-006 · RMSE (Root Mean Square Error).
     *
     * RMSE = √(Σ(error²) / n)
     *
     * @param  float[]  $errors
     */
    public static function rmse(array $errors): float
    {
        $count = count($errors);
        if ($count === 0) {
            return 0.0;
        }

        $sumSquared = 0.0;
        foreach ($errors as $error) {
            $sumSquared += ((float) $error) ** 2;
        }

        return round(sqrt($sumSquared / $count), 2);
    }

    /**
     * IND-006 · RMSE a partir de series de actual y forecast.
     *
     * @param  float[]  $actuals
     * @param  float[]  $forecasts
     */
    public static function rmseFromSeries(array $actuals, array $forecasts): float
    {
        if (count($actuals) !== count($forecasts)) {
            return 0.0;
        }

        $errors = [];
        foreach ($actuals as $i => $actual) {
            $errors[] = (float) $forecasts[$i] - (float) $actual;
        }

        return self::rmse($errors);
    }

    /**
     * IND-007 · Overdispersion (Variance-to-Mean Ratio).
     *
     * VMR = Var(Arrivals) / Mean(Arrivals)
     */
    public static function vmr(float $variance, float $mean): float
    {
        if ($mean <= 0) {
            return 0.0;
        }

        return round($variance / $mean, 4);
    }

    /**
     * IND-007 · Factor de corrección por overdispersion.
     *
     * Si VMR > 1, Erlang C subestima el personal necesario.
     */
    public static function overdispersionFactor(float $vmr): float
    {
        return max(1.0, $vmr);
    }

    /**
     * IND-008 · Intraday Reforecast.
     *
     * Forecast_Nuevo = Forecast_Original × (Actual_Acumulado / Forecast_Acumulado)
     */
    public static function intradayReforecast(
        float $originalForecast,
        float $actualToDate,
        float $forecastToDate,
    ): float {
        if ($forecastToDate <= 0) {
            return $originalForecast;
        }

        return round($originalForecast * ($actualToDate / $forecastToDate), 2);
    }

    /**
     * IND-009 · Channel Mix.
     *
     * Channel Mix = Volume_Canal / Volume_Total × 100
     */
    public static function channelMix(float $channelVolume, float $totalVolume): float
    {
        if ($totalVolume <= 0) {
            return 0.0;
        }

        return round($channelVolume / $totalVolume * 100, 2);
    }
}
