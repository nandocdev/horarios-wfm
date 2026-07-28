<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class AdherenceHeatmapWidget extends Component
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
        $hours = range(7, 21);

        $teams = Team::where('is_active', true)
            ->withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->having('employees_count', '>', 0)
            ->get()
            ->take(6);

        $rows = $teams->map(function ($team) use ($today, $hours) {
            $teamIds = [$team->id];
            $teamEmployees = Employee::whereIn('team_id', $teamIds)
                ->where('is_active', true)->pluck('id');

            $hoursData = collect();
            foreach ($hours as $h) {
                $avgAdh = AgentIntervalMetric::whereIn('employee_id', $teamEmployees)
                    ->whereDate('interval_start', $today)
                    ->whereRaw('EXTRACT(HOUR FROM interval_start) = ?', [$h])
                    ->avg('adherence');

                $val = $avgAdh ? round((float) $avgAdh, 1) : null;
                $class = $val === null ? 'bg-zinc-100 dark:bg-zinc-700'
                    : ($val >= 95 ? 'bg-green-500' : ($val >= 85 ? 'bg-yellow-500' : 'bg-red-500'));
                $hoursData->push(['hour' => $h, 'value' => $val, 'class' => $class]);
            }

            return [
                'name' => $team->name,
                'hours' => $hoursData,
            ];
        });

        return view('operations::livewire.control-tower.adherence-heatmap-widget', [
            'hours' => $hours,
            'rows' => $rows,
        ]);
    }
}
