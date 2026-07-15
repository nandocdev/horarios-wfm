<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\PersonnelModule\Models\Team;
use App\Modules\QualityModule\Models\Evaluation;
use Carbon\Carbon;
use Livewire\Component;

class TeamEvaluationSelector extends Component
{
    public ?string $selectedTeamId = null;

    public function render()
    {
        $teams = Team::active()->orderBy('name')->get();

        $employees = collect();
        if ($this->selectedTeamId) {
            $team = Team::find($this->selectedTeamId);
            if ($team) {
                $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
                $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

                $employees = $team->employees()->active()->orderBy('first_name')->get();

                $employeeIds = $employees->pluck('id')->toArray();
                $evaluationsCounts = Evaluation::whereIn('employee_id', $employeeIds)
                    ->whereDate('dteval', '>=', $startOfWeek)
                    ->whereDate('dteval', '<=', $endOfWeek)
                    ->selectRaw('employee_id, count(*) as total')
                    ->groupBy('employee_id')
                    ->pluck('total', 'employee_id');

                $employees->map(function ($emp) use ($evaluationsCounts) {
                    $emp->current_week_evaluations_count = $evaluationsCounts[$emp->id] ?? 0;

                    return $emp;
                });
            }
        }

        return view('quality::livewire.team-evaluation-selector', [
            'teams' => $teams,
            'employees' => $employees,
        ])->layout('layouts.app', ['title' => 'Seleccionar Equipo para Evaluación']);
    }
}
