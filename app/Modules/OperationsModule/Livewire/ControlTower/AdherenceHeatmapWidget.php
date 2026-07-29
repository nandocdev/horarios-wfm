<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
            ->whereHas('employees', fn ($q) => $q->where('position_id', 5)->where('is_active', true))
            ->withCount(['employees' => fn ($q) => $q->where('is_active', true)->whereIn('position_id', [1, 2])])
            ->orderBy('name')
            ->get()
            ->filter(fn ($team) => $team->employees_count > 0);

        $teamIds = $teams->pluck('id');

        $coordinators = Employee::whereIn('team_id', $teamIds)
            ->where('position_id', 5)
            ->where('is_active', true)
            ->get()
            ->keyBy('team_id');

        $metrics = collect();
        try {
            $metrics = DB::table('teams')
                ->join('employees', 'employees.team_id', '=', 'teams.id')
                ->join('agent_interval_metrics', 'agent_interval_metrics.employee_id', '=', 'employees.id')
                ->whereIn('teams.id', $teamIds)
                ->where('employees.is_active', true)
                ->whereIn('employees.position_id', [1, 2])
                ->whereDate('agent_interval_metrics.interval_start', $today)
                ->whereRaw('EXTRACT(HOUR FROM agent_interval_metrics.interval_start) BETWEEN 7 AND 21')
                ->select(
                    'teams.id as team_id',
                    DB::raw('EXTRACT(HOUR FROM agent_interval_metrics.interval_start) as hour'),
                    DB::raw('AVG(agent_interval_metrics.adherence) as avg_adherence')
                )
                ->groupBy('teams.id', 'hour')
                ->orderBy('teams.id')
                ->orderBy('hour')
                ->get()
                ->groupBy('team_id');
        } catch (QueryException $e) {
        }

        $result = $teams->map(function ($team) use ($hours, $metrics, $coordinators) {
            $teamData = $metrics->get($team->id, collect())->keyBy('hour');

            $hoursData = collect();
            foreach ($hours as $h) {
                $row = $teamData->get($h);
                $avg = $row && $row->avg_adherence !== null ? round((float) $row->avg_adherence, 1) : null;
                $class = $avg === null ? 'bg-zinc-100 dark:bg-zinc-700'
                    : ($avg >= 95 ? 'bg-green-500' : ($avg >= 85 ? 'bg-yellow-500' : 'bg-red-500'));

                $hoursData->push(['hour' => $h, 'value' => $avg, 'class' => $class]);
            }

            $coordinator = $coordinators->get($team->id);

            return [
                'name' => $team->name,
                'coordinator' => $coordinator?->full_name,
                'hours' => $hoursData,
            ];
        });

        return view('operations::livewire.control-tower.adherence-heatmap-widget', [
            'hours' => $hours,
            'rows' => $result,
        ]);
    }
}
