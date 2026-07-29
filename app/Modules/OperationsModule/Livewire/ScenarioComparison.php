<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\ForecastGroup;
use App\Modules\AnalyticsModule\Models\ForecastVersion;
use Livewire\Component;

class ScenarioComparison extends Component
{
    public ?string $selectedVersionId = null;

    public ?string $selectedGroupId = null;

    protected function queryString(): array
    {
        return [
            'selectedVersionId' => ['as' => 'version'],
            'selectedGroupId' => ['as' => 'group'],
        ];
    }

    public function selectVersion(string $id): void
    {
        $this->selectedVersionId = $id;
    }

    public function render()
    {
        $groups = ForecastGroup::with(['versions' => function ($q) {
            $q->latest()->with(['scenarios']);
        }])
            ->orderBy('name')
            ->get();

        $version = null;
        $scenarios = collect();
        $dailyTotals = collect();
        $detailedTotals = collect();
        $intervalSlots = [];

        if ($this->selectedVersionId) {
            $version = ForecastVersion::with(['scenarios.intervals', 'group'])->find($this->selectedVersionId);
            if ($version) {
                $scenarios = $version->scenarios;

                $dailyTotals = $scenarios->mapWithKeys(function ($scenario) {
                    $intervals = $scenario->intervals;
                    $totalVolume = $intervals->sum('call_volume_forecast');
                    $avgAht = $intervals->avg('aht_seconds_forecast');
                    $totalStaff = $intervals->sum('staff_required');

                    return [$scenario->id => [
                        'name' => $scenario->name,
                        'type' => $scenario->scenario_type,
                        'multiplier' => $scenario->multiplier,
                        'total_volume' => $totalVolume,
                        'avg_aht' => $avgAht,
                        'total_staff' => $totalStaff,
                        'interval_count' => $intervals->count(),
                    ]];
                });

                $scenariosById = $scenarios->keyBy('id');

                $allIntervals = $scenarios->flatMap(fn ($s) => $s->intervals)
                    ->groupBy(fn ($i) => $i->interval_start->format('Y-m-d H:i'));

                $slotCount = 0;
                foreach ($allIntervals as $timeKey => $intervalGroup) {
                    $slotCount++;
                    $row = ['slot' => $slotCount, 'label' => $timeKey];
                    foreach ($scenarios as $scenario) {
                        $interval = $intervalGroup->first(fn ($i) => $i->forecast_scenario_id === $scenario->id);
                        $row["vol_{$scenario->id}"] = $interval?->call_volume_forecast;
                        $row["aht_{$scenario->id}"] = $interval?->aht_seconds_forecast;
                        $row["staff_{$scenario->id}"] = $interval?->staff_required;
                    }
                    $intervalSlots[] = $row;
                }
            }
        }

        return view('operations::livewire.scenario-comparison', [
            'groups' => $groups,
            'version' => $version,
            'scenarios' => $scenarios,
            'dailyTotals' => $dailyTotals,
            'intervalSlots' => $intervalSlots,
        ])->layout('layouts.app', ['title' => 'Comparación de Escenarios']);
    }
}
