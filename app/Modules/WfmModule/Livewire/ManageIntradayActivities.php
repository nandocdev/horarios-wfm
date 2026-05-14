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

    public $notes;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
    }

    public function openAssignmentModal()
    {
        $this->reset(['activityDefinitionId', 'selectedEmployeeIds', 'startTime', 'endTime', 'notes']);
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

    public function assignActivity(\App\Modules\WfmModule\Actions\AssignIntradayActivityAction $action)
    {
        $this->validate([
            'activityDefinitionId' => 'required|exists:scheduled_activity_definitions,id',
            'selectedEmployeeIds' => 'required|array|min:1',
            'startTime' => 'required',
            'endTime' => 'required|after:startTime',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $dto = \App\Modules\WfmModule\DTOs\IntradayActivityDTO::fromArray([
                'activity_definition_id' => $this->activityDefinitionId,
                'employee_id' => $this->selectedEmployeeIds, // El DTO espera array
                'employee_ids' => $this->selectedEmployeeIds,
                'date' => $this->date,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'notes' => $this->notes,
            ]);

            $action->execute($dto);

            \Flux::toast('Actividad programada exitosamente.');
            $this->showAssignmentModal = false;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Flux::toast('Error al programar actividad: ' . $e->getMessage(), variant: 'danger');
        }
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
