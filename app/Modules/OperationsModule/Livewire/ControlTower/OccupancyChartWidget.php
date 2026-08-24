<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class OccupancyChartWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function render()
    {
        $today = $this->selectedDate;
        $yesterday = Carbon::parse($today)->subDay()->toDateString();
        $ids = $this->employeeIds;

        // Fallback consistente con HeroStatsWidget: sin filtro de alcance,
        // el widget opera sobre todos los empleados activos.
        if (empty($ids)) {
            $ids = Employee::where('is_active', true)->pluck('id')->toArray();
        }

        try {
            $todayMetrics = AgentIntervalMetric::whereIn('employee_id', $ids)
                ->whereDate('interval_start', $today)
                ->selectRaw('EXTRACT(HOUR FROM interval_start) as hour, AVG(occupancy) as avg_occupancy')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');

            $yesterdayMetrics = AgentIntervalMetric::whereIn('employee_id', $ids)
                ->whereDate('interval_start', $yesterday)
                ->selectRaw('EXTRACT(HOUR FROM interval_start) as hour, AVG(occupancy) as avg_occupancy')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');
        } catch (QueryException $e) {
            $todayMetrics = collect();
            $yesterdayMetrics = collect();
        }

        $hasData = $todayMetrics->isNotEmpty() || $yesterdayMetrics->isNotEmpty();

        if ($hasData) {
            $hours = range(6, 17);
            $todaySeries = [];
            $yesterdaySeries = [];
            $categories = [];

            foreach ($hours as $h) {
                $categories[] = sprintf('%02d:00', $h);
                $todaySeries[] = isset($todayMetrics[$h]) ? round((float) $todayMetrics[$h]->avg_occupancy, 1) : 0;
                $yesterdaySeries[] = isset($yesterdayMetrics[$h]) ? round((float) $yesterdayMetrics[$h]->avg_occupancy, 1) : 0;
            }

            $series = [
                ['name' => 'Hoy', 'data' => $todaySeries],
                ['name' => 'Ayer', 'data' => $yesterdaySeries],
            ];
        } else {
            $series = [];
            $categories = [];
        }

        return view('operations::livewire.control-tower.occupancy-chart-widget', [
            'hasData' => $hasData,
            'chartOptions' => $hasData ? [
                'chart' => ['type' => 'line', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'fontFamily' => 'inherit', 'height' => 220],
                'series' => $series,
                'xaxis' => ['categories' => $categories, 'labels' => ['style' => ['fontSize' => '10px']]],
                'yaxis' => ['min' => 0, 'max' => 100],
                'colors' => ['#3b82f6', '#94a3b8'],
                'stroke' => ['width' => [2, 2], 'dashArray' => [0, 4], 'curve' => 'smooth'],
                'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 2],
                'dataLabels' => ['enabled' => false],
                'legend' => ['show' => true, 'position' => 'top', 'fontSize' => '11px'],
                'tooltip' => ['y' => ['formatter' => ['__callback' => 'percent']]],
            ] : [],
        ]);
    }
}
