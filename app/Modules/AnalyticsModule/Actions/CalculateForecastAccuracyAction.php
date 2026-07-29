<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\ForecastAccuracy;
use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\ConnectModule\Models\AgentCallPerformance;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CalculateForecastAccuracyAction
{
    public function execute(
        string $forecastScenarioId,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): array {
        $scenario = ForecastScenario::with(['version.group'])->findOrFail($forecastScenarioId);
        $queueId = $scenario->version->group->reference_id ?? 'general';
        $intervalMinutes = 15;

        $forecastIntervals = ForecastInterval::where('forecast_scenario_id', $forecastScenarioId)
            ->whereDate('interval_start', '>=', $startDate->toDateString())
            ->whereDate('interval_start', '<=', $endDate->toDateString())
            ->orderBy('interval_start')
            ->get();

        if ($forecastIntervals->isEmpty()) {
            return ['processed_intervals' => 0, 'dates' => []];
        }

        $actualData = $this->loadActualData($queueId, $startDate, $endDate, $intervalMinutes);

        $dates = [];

        DB::transaction(function () use ($forecastIntervals, $actualData, $scenario, $queueId, &$dates) {
            $dailyForecasts = $forecastIntervals->groupBy(fn ($fi) => $fi->interval_start->toDateString());
            $dailyActuals = $this->indexActualsByDateInterval($actualData);

            foreach ($dailyForecasts as $date => $intervals) {
                $dayActuals = $dailyActuals[$date] ?? [];

                $dailyForecastVolume = 0;
                $dailyActualVolume = 0;
                $dailyForecastAhtSum = 0;
                $dailyActualAhtSum = 0;
                $dailyIntervalCount = 0;
                $squaredErrors = [];
                $apeValues = [];

                foreach ($intervals as $fi) {
                    $intervalKey = $fi->interval_start->format('H:i');
                    $actual = $dayActuals[$intervalKey] ?? null;

                    $forecastVolume = $fi->call_volume_forecast;
                    $actualVolume = $actual['calls'] ?? 0;
                    $forecastAht = $fi->aht_seconds_forecast;
                    $actualAht = $actual['aht'] ?? 0;

                    $dailyForecastVolume += $forecastVolume;
                    $dailyActualVolume += $actualVolume;
                    $dailyForecastAhtSum += $forecastAht;
                    $dailyActualAhtSum += $actualAht;
                    $dailyIntervalCount++;

                    $error = $forecastVolume - $actualVolume;
                    $absError = abs($error);
                    $ape = $actualVolume > 0 ? round(($absError / $actualVolume) * 100, 2) : 0;
                    $squaredErrors[] = $error * $error;
                    $apeValues[] = $ape;
                }

                $dailyMape = $dailyIntervalCount > 0 && ! empty($apeValues)
                    ? round(array_sum($apeValues) / $dailyIntervalCount, 2)
                    : 0.0;

                $dailyMse = ! empty($squaredErrors)
                    ? array_sum($squaredErrors) / count($squaredErrors)
                    : 0.0;
                $dailyRmse = round(sqrt($dailyMse), 2);

                $dailyBias = $dailyActualVolume > 0
                    ? round((($dailyForecastVolume - $dailyActualVolume) / $dailyActualVolume) * 100, 2)
                    : 0.0;

                $dailyAccuracy = round(max(0, 100 - $dailyMape), 2);

                $volumeApe = $dailyActualVolume > 0
                    ? round((abs($dailyForecastVolume - $dailyActualVolume) / $dailyActualVolume) * 100, 2)
                    : 0.0;

                ForecastAccuracy::updateOrCreate(
                    [
                        'forecast_version_id' => $scenario->forecast_version_id,
                        'evaluation_date' => $date,
                        'queue_id' => $queueId,
                    ],
                    [
                        'forecast_scenario_id' => $forecastScenarioId,
                        'forecast_call_volume' => $dailyForecastVolume,
                        'actual_call_volume' => $dailyActualVolume,
                        'forecast_aht' => $dailyIntervalCount > 0 ? round($dailyForecastAhtSum / $dailyIntervalCount, 2) : 0,
                        'actual_aht' => $dailyIntervalCount > 0 ? round($dailyActualAhtSum / $dailyIntervalCount, 2) : 0,
                        'volume_error' => $dailyForecastVolume - $dailyActualVolume,
                        'volume_abs_error' => abs($dailyForecastVolume - $dailyActualVolume),
                        'volume_ape' => $volumeApe,
                        'mape' => $dailyMape,
                        'bias' => $dailyBias,
                        'rmse' => $dailyRmse,
                        'accuracy' => $dailyAccuracy,
                    ],
                );

                $dates[] = [
                    'date' => $date,
                    'forecast_volume' => $dailyForecastVolume,
                    'actual_volume' => $dailyActualVolume,
                    'mape' => $dailyMape,
                    'bias' => $dailyBias,
                    'rmse' => $dailyRmse,
                    'accuracy' => $dailyAccuracy,
                ];
            }
        });

        return [
            'processed_intervals' => $forecastIntervals->count(),
            'dates' => $dates,
        ];
    }

    private function loadActualData(string $queueId, CarbonInterface $start, CarbonInterface $end, int $intervalMinutes): Collection
    {
        $records = AgentCallPerformance::where('csq_name', $queueId)
            ->whereBetween('start_time', [$start->startOfDay(), $end->endOfDay()])
            ->get(['start_time', 'talk_time', 'work_time']);

        $grouped = [];

        foreach ($records as $r) {
            $ts = $r->start_time;
            if (! $ts) {
                continue;
            }
            $date = $ts->toDateString();
            $minutesSinceMidnight = (int) $ts->format('H') * 60 + (int) $ts->format('i');
            $slot = (int) floor($minutesSinceMidnight / $intervalMinutes);
            $intervalKey = sprintf('%02d:%02d', intdiv($slot * $intervalMinutes, 60), ($slot * $intervalMinutes) % 60);

            $compoundKey = $date.'|'.$intervalKey;

            if (! isset($grouped[$compoundKey])) {
                $grouped[$compoundKey] = ['date' => $date, 'interval' => $intervalKey, 'calls' => 0, 'total_handle_time' => 0];
            }

            $grouped[$compoundKey]['calls']++;
            $grouped[$compoundKey]['total_handle_time'] += ($r->talk_time ?? 0) + ($r->work_time ?? 0);
        }

        $result = collect();
        foreach ($grouped as $compoundKey => $g) {
            $result->put($compoundKey, [
                'date' => $g['date'],
                'interval' => $g['interval'],
                'calls' => $g['calls'],
                'aht' => $g['calls'] > 0 ? round($g['total_handle_time'] / $g['calls'], 2) : 0,
            ]);
        }

        return $result;
    }

    private function indexActualsByDateInterval(Collection $actuals): array
    {
        $indexed = [];

        foreach ($actuals as $item) {
            $date = $item['date'];
            $interval = $item['interval'];
            $indexed[$date][$interval] = $item;
        }

        return $indexed;
    }
}
