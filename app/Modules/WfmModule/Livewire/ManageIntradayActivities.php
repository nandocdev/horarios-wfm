<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ManageIntradayActivities extends Component
{
    use WithPagination;

    // Filtros
    public $date;

    public $selectedTeamId;

    // Formulario de asignación
    public $showAssignmentModal = false;

    public $activityDefinitionId;

    public $selectedEmployeeIds = [];

    public $startTime;

    public $endTime;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
    }

    public function openAssignmentModal()
    {
        $this->reset(['activityDefinitionId', 'selectedEmployeeIds', 'startTime', 'endTime']);
        $this->startTime = now()->format('H:i');
        $this->showAssignmentModal = true;
    }

    public function updatedActivityDefinitionId($value)
    {
        if ($value) {
            $definition = ScheduledActivityDefinition::find($value);
            if ($definition && $definition->default_duration_minutes) {
                $start = Carbon::parse($this->startTime);
                $this->endTime = $start->addMinutes($definition->default_duration_minutes)->format('H:i');
            }
        }
    }

    public function assignActivity()
    {
        $this->validate([
            'activityDefinitionId' => 'required|exists:scheduled_activity_definitions,id',
            'selectedEmployeeIds' => 'required|array|min:1',
            'startTime' => 'required',
            'endTime' => 'required|after:startTime',
        ]);

        $definition = ScheduledActivityDefinition::find($this->activityDefinitionId);
        $startRange = Carbon::parse($this->date.' '.$this->startTime)->toIso8601String();
        $endRange = Carbon::parse($this->date.' '.$this->endTime)->toIso8601String();

        \DB::transaction(function () use ($definition, $startRange, $endRange) {
            foreach ($this->selectedEmployeeIds as $employeeId) {
                IntradayActivity::create([
                    'employee_id' => $employeeId,
                    'activity_type_id' => $definition->activity_type_id,
                    'time_range' => "[$startRange, $endRange)",
                ]);
            }
        });

        $this->showAssignmentModal = false;
        \Flux::toast('Actividad programada exitosamente para '.count($this->selectedEmployeeIds).' operadores.');
    }

    public function deleteActivity($id)
    {
        IntradayActivity::findOrFail($id)->delete();
        \Flux::toast('Actividad eliminada.');
    }

    public function render()
    {
        $query = IntradayActivity::with(['employee', 'employee.team', 'activityType'])
            ->whereRaw('time_range && tstzrange(?, ?)', [
                Carbon::parse($this->date)->startOfDay()->toIso8601String(),
                Carbon::parse($this->date)->endOfDay()->toIso8601String(),
            ]);

        if ($this->selectedTeamId) {
            $query->whereHas('employee', fn ($q) => $q->where('team_id', $this->selectedTeamId));
        }

        return view('wfm::livewire.manage-intraday-activities', [
            'activities' => $query->orderByRaw('lower(time_range) asc')->paginate(20),
            'definitions' => ScheduledActivityDefinition::where('is_active', true)->get(),
            'teams' => Team::where('is_active', true)->get(),
            'employees' => $this->selectedTeamId
                ? Employee::with('team')->where('team_id', $this->selectedTeamId)->where('is_active', true)->get()
                : Employee::with('team')->where('is_active', true)->get(),
        ])->layout('layouts.app');
    }
}
