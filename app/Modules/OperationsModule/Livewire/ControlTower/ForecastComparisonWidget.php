<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\AnalyticsModule\Models\ForecastInterval;
use App\Modules\AnalyticsModule\Models\ForecastScenario;
use App\Modules\ConnectModule\Models\AgentCallPerformance;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ForecastComparisonWidget extends Component
{
    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-72 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render()
    {
        $today = Carbon::parse($this->selectedDate);

        $latestScenario = ForecastScenario::whereHas('version', fn ($q) => $q->where('status', 'published'))
            ->latest()
            ->first();

        $forecastData = collect();
        $actualData = collect();

        if ($latestScenario) {
            $forecastData = ForecastInterval::where('forecast_scenario_id', $latestScenario->id)
                ->whereDate('interval_start', $today)
                ->orderBy('interval_start')
                ->get();
        }

        $actualCalls = AgentCallPerformance::whereDate('start_time', $today)
            ->selectRaw('EXTRACT(HOUR FROM start_time) as hour, COUNT(*) as calls')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $hours = range(7, 21);
        $forecastSeries = [];
        $actualSeries = [];
        $categories = [];

        foreach ($hours as $h) {
            $label = sprintf('%02d:00', $h);
            $categories[] = $label;

            $forecastCount = $forecastData->filter(function ($fi) use ($h) {
                $fiHour = (int) $fi->interval_start->format('H');

                return $fiHour === $h;
            })->sum('call_volume_forecast');

            $forecastSeries[] = $forecastCount;
            $actualSeries[] = (int) ($actualCalls[$h]->calls ?? 0);
        }

        $chartOptions = json_encode([
            'chart' => ['type' => 'bar', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'fontFamily' => 'inherit', 'height' => 240],
            'series' => [
                ['name' => 'Forecast', 'data' => $forecastSeries],
                ['name' => 'Real', 'data' => $actualSeries],
            ],
            'xaxis' => ['categories' => $categories, 'labels' => ['style' => ['fontSize' => '10px']]],
            'yaxis' => ['labels' => ['formatter' => 'function(v){return Number.isInteger(v)?v:""}']],
            'colors' => ['#6366f1', '#22c55e'],
            'plotOptions' => ['bar' => ['columnWidth' => '60%']],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 2],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => true, 'position' => 'top', 'fontSize' => '11px'],
            'tooltip' => ['y' => ['formatter' => 'function(v){return Number.isInteger(v)?v+" llamadas":v}']],
        ]);

        return view('operations::livewire.control-tower.forecast-comparison-widget', [
            'chartOptions' => $chartOptions,
            'hasForecast' => $latestScenario !== null,
        ]);
    }
}
