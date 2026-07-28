<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Actions;

use App\Modules\AnalyticsModule\Models\ForecastGroup;
use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ImportForecastAction
{
    /**
     * @param  array<int, array{interval_start: string, interval_end: string, call_volume: int, talk_time_seconds: int, aht_seconds: float, staff_required: float}>  $intervals
     */
    public function execute(
        string $groupName,
        string $groupType,
        ?string $referenceId,
        string $versionName,
        ?int $userId,
        array $intervals,
    ): ForecastVersion {
        return DB::transaction(function () use ($groupName, $groupType, $referenceId, $versionName, $userId, $intervals) {
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
                'name' => $versionName,
                'status' => 'draft',
                'generated_by' => $userId,
                'generated_at' => CarbonImmutable::now(),
                'description' => null,
            ]);

            $scenario = $version->scenarios()->create([
                'name' => 'Base',
                'scenario_type' => 'base',
                'multiplier' => 1.00,
                'is_active' => true,
            ]);

            $intervalModels = [];
            foreach ($intervals as $i) {
                $intervalModels[] = new ForecastInterval([
                    'forecast_scenario_id' => $scenario->id,
                    'interval_start' => $i['interval_start'],
                    'interval_end' => $i['interval_end'],
                    'interval_minutes' => 15,
                    'call_volume_forecast' => $i['call_volume'],
                    'talk_time_seconds_forecast' => $i['talk_time_seconds'],
                    'aht_seconds_forecast' => $i['aht_seconds'],
                    'staff_required' => $i['staff_required'],
                ]);
            }

            $scenario->intervals()->saveMany($intervalModels);

            return $version->load(['group', 'scenarios', 'scenarios.intervals']);
        });
    }
}
