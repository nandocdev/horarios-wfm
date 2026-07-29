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
            $raw = DB::table('agent_state_transitions')
                ->join('employees', 'employees.id', '=', 'agent_state_transitions.employee_id')
                ->whereIn('employees.team_id', $teamIds)
                ->where('employees.is_active', true)
                ->whereIn('employees.position_id', [1, 2])
                ->whereDate('agent_state_transitions.transition_time', $today)
                ->whereRaw('EXTRACT(HOUR FROM agent_state_transitions.transition_time) BETWEEN 7 AND 21')
                ->select(
                    'employees.team_id',
                    DB::raw('EXTRACT(HOUR FROM agent_state_transitions.transition_time) as hour'),
                    DB::raw("COUNT(DISTINCT agent_state_transitions.employee_id) FILTER (WHERE TRIM(agent_state_transitions.agent_state) IN ('Talking','Ready','Work','Reserved')) as productive"),
                    DB::raw('COUNT(DISTINCT agent_state_transitions.employee_id) as total')
                )
                ->groupBy('employees.team_id', 'hour')
                ->orderBy('employees.team_id')
                ->orderBy('hour')
                ->get()
                ->groupBy('team_id');
        } catch (QueryException $e) {
            $raw = collect();
        }

        $result = $teams->map(function ($team) use ($hours, $raw, $coordinators) {
            $teamData = $raw->get($team->id, collect())->keyBy('hour');

            $hoursData = collect();
            foreach ($hours as $h) {
                $row = $teamData->get($h);
                $pct = $row && $row->total > 0
                    ? round(($row->productive / $row->total) * 100, 1)
                    : null;
                $class = $pct === null ? 'bg-zinc-100 dark:bg-zinc-700'
                    : ($pct >= 85 ? 'bg-green-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-red-500'));

                $hoursData->push(['hour' => $h, 'value' => $pct, 'class' => $class]);
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
