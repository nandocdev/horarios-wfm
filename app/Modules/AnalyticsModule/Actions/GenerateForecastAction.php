<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\ForecastGroup;
use App\Modules\AnalyticsModule\Models\ForecastVersion;
use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\CallQueue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GenerateForecastAction
{
    private const SECONDS_PER_INTERVAL = 900;

    public function execute(
        string $groupName,
        string $groupType,
        ?string $referenceId,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        int $intervalMinutes = 15,
        int $historicalWeeks = 4,
        ?int $userId = null,
    ): ForecastVersion {
        return DB::transaction(function () use (
            $groupName, $groupType, $referenceId, $startDate, $endDate,
            $intervalMinutes, $historicalWeeks, $userId,
        ) {
            $group = ForecastGroup::firstOrCreate(
                [
                    'group_type' => $groupType,
                    'reference_id' => $referenceId,
                ],
                [
                    'name' => $groupName,
                    'description' => null,
                    'is_active' => true,
                ],
            );

            $maxVersion = $group->versions()->max('version_number') ?? 0;

            $version = $group->versions()->create([
                'version_number' => $maxVersion + 1,
                'name' => $groupName.' - '.$startDate->format('Y-m-d').' a '.$endDate->format('Y-m-d'),
                'status' => 'draft',
                'generated_by' => $userId,
                'generated_at' => CarbonImmutable::now(),
                'description' => 'Generado por promedio histórico de '.$historicalWeeks.' semanas',
            ]);

            $scenario = $version->scenarios()->create([
                'name' => 'Base',
                'scenario_type' => 'base',
                'multiplier' => 1.00,
                'is_active' => true,
            ]);

            $dates = $this->generateDateRange($startDate, $endDate);
            $intervalsPerDay = (24 * 60) / $intervalMinutes;

            $historyStart = $startDate->copy()->subWeeks($historicalWeeks);
            $historyEnd = $endDate;

            $historicalData = $this->loadHistoricalData($referenceId, $historyStart, $historyEnd, $intervalMinutes);

            $batch = [];
            foreach ($dates as $date) {
                $dayOfWeek = (int) $date->format('N');

                for ($slot = 0; $slot < $intervalsPerDay; $slot++) {
                    $intervalStart = $date->copy()->startOfDay()->addMinutes($slot * $intervalMinutes);
                    $intervalEnd = $intervalStart->copy()->addMinutes($intervalMinutes);

                    $key = $dayOfWeek.'_'.$slot;
                    $history = $historicalData[$key] ?? null;

                    $callVolume = $history ? (int) round($history['avg_calls']) : 0;
                    $talkTime = $history ? (int) round($history['avg_talk_time']) : 0;
                    $aht = $history && $callVolume > 0
                        ? round($talkTime / $callVolume, 2)
                        : 0.0;
                    $staffRequired = $callVolume > 0 && $aht > 0
                        ? round(($callVolume * $aht) / self::SECONDS_PER_INTERVAL / 3600, 2)
                        : 0.0;

                    $batch[] = [
                        'id' => (string) Str::ulid(),
                        'forecast_scenario_id' => $scenario->id,
                        'interval_start' => $intervalStart,
                        'interval_end' => $intervalEnd,
                        'interval_minutes' => $intervalMinutes,
                        'call_volume_forecast' => $callVolume,
                        'talk_time_seconds_forecast' => $talkTime,
                        'aht_seconds_forecast' => $aht,
                        'staff_required' => $staffRequired,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('forecast_intervals')->insert($batch);

            return $version->load(['group', 'scenarios', 'scenarios.intervals']);
        });
    }

    private function loadHistoricalData(?string $referenceId, CarbonInterface $start, CarbonInterface $end, int $intervalMinutes): array
    {
        $query = AgentCallPerformance::whereBetween('start_time', [$start, $end]);

        if ($referenceId !== null) {
            $queueName = CallQueue::where('id', $referenceId)->value('name') ?? $referenceId;
            $query->where('csq_name', $queueName);
        }

        $records = $query->get(['start_time', 'talk_time', 'hold_time', 'work_time', 'csq_name']);

        $grouped = [];
        foreach ($records as $record) {
            $ts = $record->start_time;
            if (! $ts) {
                continue;
            }
            $dayOfWeek = (int) $ts->format('N');
            $minutesSinceMidnight = (int) $ts->format('H') * 60 + (int) $ts->format('i');
            $slot = (int) floor($minutesSinceMidnight / $intervalMinutes);
            $key = $dayOfWeek.'_'.$slot;

            if (! isset($grouped[$key])) {
                $grouped[$key] = ['calls' => 0, 'total_talk_time' => 0, 'total_work_time' => 0];
            }

            $grouped[$key]['calls']++;
            $grouped[$key]['total_talk_time'] += $record->talk_time ?? 0;
            $grouped[$key]['total_work_time'] += $record->work_time ?? 0;
        }

        $result = [];
        foreach ($grouped as $key => $data) {
            $numWeeks = $start->diffInWeeks($end);
            $weeksWithData = max(1, $numWeeks);
            $result[$key] = [
                'avg_calls' => $data['calls'] / $weeksWithData,
                'avg_talk_time' => ($data['total_talk_time'] + $data['total_work_time']) / $weeksWithData,
            ];
        }

        return $result;
    }

    private function generateDateRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $dates = [];
        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            $dates[] = $current->copy();
            $current = $current->addDay();
        }

        return $dates;
    }
}
