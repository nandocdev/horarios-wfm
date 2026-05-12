<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Actions\AssignTeamWeeklyScheduleAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Livewire\Component;

class WeeklyPlanningTeams extends Component
{
    public WeeklySchedule $week;

    public array $teamSchedules = [];

    public array $teamStart = [];

    public array $teamEnd = [];

    public array $teamLunch = [];

    public array $teamBreak = [];

    public function mount(WeeklySchedule $week): void
    {
        $this->week = $week;
        $this->loadTeamSchedules();
    }

    public function loadTeamSchedules(): void
    {
        $this->teamSchedules = [];
        $this->teamStart = [];
        $this->teamEnd = [];
        $this->teamLunch = [];
        $this->teamBreak = [];

        $assignments = WeeklyTeamAssignment::where('weekly_schedule_id', $this->week->id)
            ->where('day_of_week', 1) // Usamos el lunes como referencia
            ->get();

        foreach ($assignments as $assignment) {
            $this->teamSchedules[$assignment->team_id] = $assignment->schedule_id;
            $this->teamStart[$assignment->team_id] = $assignment->start_time ? substr($assignment->start_time, 0, 5) : null;
            $this->teamEnd[$assignment->team_id] = $assignment->end_time ? substr($assignment->end_time, 0, 5) : null;
            $this->teamLunch[$assignment->team_id] = $assignment->lunch_start_time ? substr($assignment->lunch_start_time, 0, 5) : null;
            $this->teamBreak[$assignment->team_id] = $assignment->break_start_time ? substr($assignment->break_start_time, 0, 5) : null;
        }
    }

    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'teamSchedules.') && $value) {
            $teamId = explode('.', $property)[1];
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->teamStart[$teamId] = substr($schedule->start_time, 0, 5);
                $this->teamEnd[$teamId] = substr($schedule->end_time, 0, 5);
            }
        }
    }

    public function assignToTeam(int $teamId, AssignTeamWeeklyScheduleAction $action): void
    {
        $this->authorize('schedules.manage');

        $scheduleId = $this->teamSchedules[$teamId] ?? null;
        $startTime = $this->teamStart[$teamId] ?? null;
        $endTime = $this->teamEnd[$teamId] ?? null;
        $lunchStart = $this->teamLunch[$teamId] ?? null;
        $breakStart = $this->teamBreak[$teamId] ?? null;

        if (! $scheduleId) {
            \Flux::toast('Seleccione un turno.', variant: 'danger');

            return;
        }

        $action->execute($this->week->id, $teamId, (int) $scheduleId, $lunchStart, $breakStart, $startTime, $endTime);

        $this->loadTeamSchedules();

        \Flux::toast('Horario asignado al equipo y miembros.');
    }

    public function render()
    {
        $user = auth()->user();
        $employee = $user->employee;

        $query = Team::with('supervisor')->where('is_active', true);

        // Si no es admin/wfm, filtrar por subordinación
        if (! $user->hasRole(['admin', 'wfm', 'director']) && $employee) {
            $teamIds = $employee->getManagedTeamIds(); // Asumiendo que este helper existe en Employee
            $query->whereIn('id', $teamIds);
        }

        return view('wfm::livewire.weekly-planning-teams', [
            'teams' => $query->orderBy('name')->get(),
            'schedules' => Schedule::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
