<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Analytics;

use App\Modules\AnalyticsModule\Actions\GenerateCapacityPlanAction;
use App\Modules\AnalyticsModule\Models\ForecastGroup;
use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\AnalyticsModule\Models\ForecastVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenerateCapacityPlanActionTest extends TestCase
{
    public function test_generates_plan_with_ulids_and_large_coverage(): void
    {
        $group = ForecastGroup::create([
            'name' => 'CSQ_TEST',
            'group_type' => 'queue',
            'reference_id' => '3',
            'is_active' => true,
        ]);

        $version = ForecastVersion::create([
            'forecast_group_id' => $group->id,
            'version_number' => 1,
            'name' => 'V1',
            'status' => 'active',
        ]);

        $scenario = ForecastScenario::create([
            'forecast_version_id' => $version->id,
            'name' => 'Base',
            'scenario_type' => 'base',
            'is_active' => true,
        ]);

        $date = CarbonImmutable::parse('2026-08-25');

        // Intervalo con required muy bajo para forzar coverage > numeric(5,2).
        ForecastInterval::create([
            'forecast_scenario_id' => $scenario->id,
            'interval_start' => $date->setTime(6, 0),
            'interval_end' => $date->setTime(6, 15),
            'interval_minutes' => 15,
            'call_volume_forecast' => 3,
            'talk_time_seconds_forecast' => 457,
            'aht_seconds_forecast' => 95.67,
            'staff_required' => 0.32,
        ]);

        $plan = app(GenerateCapacityPlanAction::class)->execute(
            $scenario->id,
            $date,
            15.0,
        );

        $this->assertDatabaseHas('capacity_intervals', [
            'capacity_plan_id' => $plan->id,
        ]);

        $interval = $plan->intervals->first();
        $this->assertNotNull($interval->id);

        // La columna coverage ampliada acepta valores mayores a numeric(5,2).
        DB::table('capacity_intervals')
            ->where('id', $interval->id)
            ->update(['coverage' => 60971.43]);

        $this->assertDatabaseHas('capacity_intervals', [
            'id' => $interval->id,
            'coverage' => 60971.43,
        ]);
    }
}
