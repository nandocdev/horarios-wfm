<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\AssignEmployeesToTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeesToTeamDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Gestiona la asignación de empleados a equipos.
 */
class ManageTeamAssignments extends Component
{
    public int $selectedTeamId = 0;

    public ?int $supervisor_id = null;

    public array $selectedUnassigned = [];

    public array $selectedAssigned = [];

    protected $listeners = ['refresh' => '$refresh'];

    /**
     * Carga el supervisor actual al cambiar de equipo.
     */
    public function updatedSelectedTeamId($value): void
    {
        $this->supervisor_id = Team::find($value)?->supervisor_id;
        $this->selectedAssigned = [];
        $this->selectedUnassigned = [];
    }

    /**
     * Actualiza el supervisor del equipo.
     */
    public function updateSupervisor(): void
    {
        if (! $this->selectedTeamId) {
            return;
        }

        $team = Team::find($this->selectedTeamId);
        if ($team instanceof Team) {
            $team->update(['supervisor_id' => $this->supervisor_id]);
        }

        // Sincronizar parent_id a todos los miembros actuales
        Employee::whereHas('currentTeamMember', function ($q) {
            $q->where('team_id', $this->selectedTeamId);
        })->update(['parent_id' => $this->supervisor_id]);

        session()->flash('success', 'Supervisor del equipo actualizado y jerarquía sincronizada.');
    }

    /**
     * Reglas de validación.
     */
    protected function rules(): array
    {
        return [
            'selectedTeamId' => 'required|exists:teams,id',
        ];
    }

    /**
     * Obtiene los equipos disponibles.
     */
    public function getTeamsProperty(): Collection
    {
        return Team::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Obtiene los supervisores disponibles para asignar al equipo.
     */
    public function getSupervisorsProperty(): Collection
    {
        return Employee::where('is_manager', true)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Obtiene empleados sin asignar a ningún equipo.
     */
    public function getUnassignedEmployeesProperty(): Collection
    {
        return Employee::whereDoesntHave('currentTeamMember')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Obtiene empleados asignados al equipo seleccionado.
     */
    public function getAssignedEmployeesProperty(): Collection
    {
        if (! $this->selectedTeamId) {
            return collect();
        }

        return Employee::whereHas('currentTeamMember', function ($q) {
            $q->where('team_id', $this->selectedTeamId)->where('is_active', true);
        })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Asigna empleados seleccionados al equipo.
     */
    public function assignToTeam(): void
    {
        $this->validate();

        if (empty($this->selectedUnassigned)) {
            $this->addError('selectedUnassigned', 'Selecciona al menos un empleado para asignar.');

            return;
        }

        $dto = new AssignEmployeesToTeamDTO(
            teamId: $this->selectedTeamId,
            employeeIds: $this->selectedUnassigned
        );

        app(AssignEmployeesToTeamAction::class)->assign($dto);

        $this->selectedUnassigned = [];
        $this->dispatch('refresh');
        session()->flash('success', 'Empleados asignados al equipo correctamente.');
    }

    /**
     * Desasigna empleados seleccionados del equipo.
     */
    public function unassignFromTeam(): void
    {
        if (empty($this->selectedAssigned)) {
            $this->addError('selectedAssigned', 'Selecciona al menos un empleado para desasignar.');

            return;
        }

        $dto = new AssignEmployeesToTeamDTO(
            teamId: $this->selectedTeamId,
            employeeIds: $this->selectedAssigned
        );

        app(AssignEmployeesToTeamAction::class)->unassign($dto);
        $this->selectedAssigned = [];
        $this->dispatch('refresh');
        session()->flash('success', 'Empleados desasignados del equipo correctamente.');
    }

    /**
     * Renderiza el componente.
     */
    public function render()
    {
        return view('personnel::manage-team-assignments', [
            'teams' => $this->teams,
            'unassignedEmployees' => $this->unassignedEmployees,
            'assignedEmployees' => $this->assignedEmployees,
        ]);
    }
}
