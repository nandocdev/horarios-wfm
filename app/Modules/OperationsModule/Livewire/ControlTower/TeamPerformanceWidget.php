<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class TeamPerformanceWidget extends Component
{
    public array $employeeIds = [];

    public string $selectedDate;

    public function placeholder()
    {
        return '<div class="h-48 bg-zinc-100 dark:bg-zinc-800 rounded-xl animate-pulse"></div>';
    }

    public function render()
    {
        $today = $this->selectedDate;

        $teams = Team::where('is_active', true)
            ->withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->filter(fn ($team) => $team->employees_count > 0)
            ->map(function ($team) use ($today) {
                $teamIds = [$team->id];
                $teamEmployees = Employee::whereIn('team_id', $teamIds)
                    ->where('is_active', true)
                    ->pluck('id');

                try {
                    $metrics = AgentIntervalMetric::whereIn('employee_id', $teamEmployees)
                        ->whereDate('interval_start', $today)
                        ->selectRaw('
                            AVG(occupancy) as avg_occupancy,
                            AVG(adherence) as avg_adherence,
                            AVG(utilization) as avg_utilization,
                            SUM(calls_handled) as total_calls
                        ')
                        ->first();
                } catch (QueryException $e) {
                    $metrics = null;
                }

                $occupancy = $metrics && $metrics->avg_occupancy ? round((float) $metrics->avg_occupancy, 1) : 0;
                $adherence = $metrics && $metrics->avg_adherence ? round((float) $metrics->avg_adherence, 1) : 0;

                $occupancyClass = match (true) {
                    $occupancy >= 85 && $occupancy <= 92 => 'text-green-600 dark:text-green-400',
                    $occupancy > 92 => 'text-yellow-600 dark:text-yellow-400',
                    $occupancy > 0 => 'text-red-600 dark:text-red-400',
                    default => 'text-zinc-400',
                };

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'members' => $team->employees_count,
                    'occupancy' => $occupancy,
                    'adherence' => $adherence,
                    'calls' => $metrics ? (int) $metrics->total_calls : 0,
                    'occupancyClass' => $occupancyClass,
                ];
            })
            ->sortByDesc('calls')
            ->values()
            ->take(8);

        return view('operations::livewire.control-tower.team-performance-widget', [
            'teams' => $teams,
        ]);
    }
}
