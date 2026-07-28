<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Modules\ConnectModule\Models\CallRecord;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class SlaAsaChartWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-64 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render()
    {
        $today = $this->selectedDate;
        $yesterday = Carbon::parse($today)->subDay()->toDateString();

        $days = [$yesterday, $today];
        $todaySla = [];
        $todayAsa = [];
        $yesterdaySla = [];
        $yesterdayAsa = [];
        $categories = [];

        $hours = range(7, 21);
        foreach ($hours as $h) {
            $label = sprintf('%02d:00', $h);
            $categories[] = $label;

            $todayStats = CallRecord::whereIn('employee_id', $this->employeeIds)
                ->whereDate('ivr_started_at', $today)
                ->whereRaw('EXTRACT(HOUR FROM ivr_started_at) = ?', [$h])
                ->where('contact_disposition', ContactDisposition::Handled->value)
                ->selectRaw('COUNT(*) as total, AVG(queue_time) as avg_asa, SUM(CASE WHEN queue_time <= 20 THEN 1 ELSE 0 END) as sla_count')
                ->first();

            $todaySla[] = $todayStats && $todayStats->total > 0
                ? round(($todayStats->sla_count / $todayStats->total) * 100, 1) : 0;
            $todayAsa[] = $todayStats && $todayStats->avg_asa ? round((float) $todayStats->avg_asa, 0) : 0;

            $yesterdayStats = CallRecord::whereIn('employee_id', $this->employeeIds)
                ->whereDate('ivr_started_at', $yesterday)
                ->whereRaw('EXTRACT(HOUR FROM ivr_started_at) = ?', [$h])
                ->where('contact_disposition', ContactDisposition::Handled->value)
                ->selectRaw('COUNT(*) as total, AVG(queue_time) as avg_asa, SUM(CASE WHEN queue_time <= 20 THEN 1 ELSE 0 END) as sla_count')
                ->first();

            $yesterdaySla[] = $yesterdayStats && $yesterdayStats->total > 0
                ? round(($yesterdayStats->sla_count / $yesterdayStats->total) * 100, 1) : 0;
            $yesterdayAsa[] = $yesterdayStats && $yesterdayStats->avg_asa ? round((float) $yesterdayStats->avg_asa, 0) : 0;
        }

        $chartOptions = json_encode([
            'chart' => ['type' => 'line', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'fontFamily' => 'inherit', 'height' => 220],
            'series' => [
                ['name' => 'SLA Hoy', 'data' => $todaySla, 'yAxis' => 0],
                ['name' => 'ASA Hoy (s)', 'data' => $todayAsa, 'yAxis' => 1],
                ['name' => 'SLA Ayer', 'data' => $yesterdaySla, 'yAxis' => 0],
                ['name' => 'ASA Ayer (s)', 'data' => $yesterdayAsa, 'yAxis' => 1],
            ],
            'xaxis' => ['categories' => $categories, 'labels' => ['style' => ['fontSize' => '10px']]],
            'yaxis' => [
                ['min' => 0, 'max' => 100, 'labels' => ['formatter' => 'function(v){return v+"%"}'], 'title' => ['text' => 'SLA', 'style' => ['fontSize' => '10px']]],
                ['opposite' => true, 'labels' => ['formatter' => 'function(v){return v+"s"}'], 'title' => ['text' => 'ASA', 'style' => ['fontSize' => '10px']]],
            ],
            'colors' => ['#22c55e', '#3b82f6', '#86efac', '#93c5fd'],
            'stroke' => ['width' => [2, 1.5, 2, 1.5], 'dashArray' => [0, 0, 4, 4], 'curve' => 'smooth'],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 2],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => true, 'position' => 'top', 'fontSize' => '10px'],
            'tooltip' => [
                'shared' => true,
                'y' => [
                    ['formatter' => 'function(v){return v+"%"}'],
                    ['formatter' => 'function(v){return v+"s"}'],
                    ['formatter' => 'function(v){return v+"%"}'],
                    ['formatter' => 'function(v){return v+"s"}'],
                ],
            ],
        ]);

        return view('operations::livewire.control-tower.sla-asa-chart-widget', [
            'chartOptions' => $chartOptions,
        ]);
    }
}
