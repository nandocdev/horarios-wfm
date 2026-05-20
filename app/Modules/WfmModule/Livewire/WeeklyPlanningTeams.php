<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
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
            $this->teamStart[$assignment->team_id] = $this->formatTime($assignment->start_time);
            $this->teamEnd[$assignment->team_id] = $this->formatTime($assignment->end_time);
            $this->teamLunch[$assignment->team_id] = $this->formatTime($assignment->lunch_start_time);
            $this->teamBreak[$assignment->team_id] = $this->formatTime($assignment->break_start_time);
        }
    }

    private function formatTime($time): ?string
    {
        if (! $time) {
            return null;
        }

        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return substr((string) $time, 0, 5);
    }

    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'teamSchedules.') && $value) {
            $teamId = explode('.', $property)[1];
            $schedule = Schedule::find($value);
            if ($schedule) {
                $this->teamStart[$teamId] = $this->formatTime($schedule->start_time);
                $this->teamEnd[$teamId] = $this->formatTime($schedule->end_time);
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
            'days' => [
                1 => __('Lunes'),
                2 => __('Martes'),
                3 => __('Miércoles'),
                4 => __('Jueves'),
                5 => __('Viernes'),
                6 => __('Sábado'),
                7 => __('Domingo'),
            ],
        ]);
    }
}
