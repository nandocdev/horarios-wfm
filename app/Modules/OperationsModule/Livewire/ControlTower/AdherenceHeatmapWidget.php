<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

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
            ->withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->filter(fn ($team) => $team->employees_count > 0)
            ->take(6);

        $teamIds = $teams->pluck('id');

        $metricsByTeamHour = collect();
        try {
            $rows = DB::table('teams')
                ->join('employees', 'employees.team_id', '=', 'teams.id')
                ->join('agent_interval_metrics', 'agent_interval_metrics.employee_id', '=', 'employees.id')
                ->whereIn('teams.id', $teamIds)
                ->where('employees.is_active', true)
                ->whereDate('agent_interval_metrics.interval_start', $today)
                ->whereRaw('EXTRACT(HOUR FROM agent_interval_metrics.interval_start) BETWEEN 7 AND 21')
                ->select(
                    'teams.id as team_id',
                    'teams.name as team_name',
                    DB::raw('EXTRACT(HOUR FROM agent_interval_metrics.interval_start) as hour'),
                    DB::raw('AVG(agent_interval_metrics.adherence) as avg_adherence')
                )
                ->groupBy('teams.id', 'teams.name', 'hour')
                ->orderBy('teams.name')
                ->orderBy('hour')
                ->get()
                ->groupBy('team_id');
        } catch (QueryException $e) {
            $rows = collect();
        }

        $result = $teams->map(function ($team) use ($hours, $rows) {
            $teamData = $rows->get($team->id, collect())->keyBy('hour');

            $hoursData = collect();
            foreach ($hours as $h) {
                $row = $teamData->get($h);
                $avg = $row && $row->avg_adherence !== null ? round((float) $row->avg_adherence, 1) : null;
                $class = $avg === null ? 'bg-zinc-100 dark:bg-zinc-700'
                    : ($avg >= 95 ? 'bg-green-500' : ($avg >= 85 ? 'bg-yellow-500' : 'bg-red-500'));

                $hoursData->push(['hour' => $h, 'value' => $avg, 'class' => $class]);
            }

            return [
                'name' => $team->name,
                'hours' => $hoursData,
            ];
        });

        return view('operations::livewire.control-tower.adherence-heatmap-widget', [
            'hours' => $hours,
            'rows' => $result,
        ]);
    }
}
