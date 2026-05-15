<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\ToggleTeamStatusAction;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

/**
 * Componente Livewire para mostrar los detalles de un equipo.
 */
class ShowTeam extends Component
{
    public Team $team;

    public function mount(Team $team): void
    {
        $this->authorize('view', $team);
        $this->loadTeam();
    }

    public function loadTeam(): void
    {
        $this->team = $this->team->load(['users', 'supervisor']);
    }

    /**
     * Remueve un empleado del equipo.
     */
    public function removeMember(int $employeeId): void
    {
        $this->authorize('update', $this->team);

        $action = new \App\Modules\PersonnelModule\Actions\RemoveEmployeeFromTeamAction;
        $action->execute(new \App\Modules\PersonnelModule\DTOs\RemoveEmployeeFromTeamDTO(
            employee_id: $employeeId,
            team_id: $this->team->id,
            left_at: now()->format('Y-m-d')
        ));

        $this->loadTeam();
        
        \Flux::toast(
            heading: 'Miembro removido',
            text: 'El empleado ha sido desvinculado del equipo correctamente.',
            variant: 'success'
        );
    }

    /** @var array<int, string> */
    public array $availableEmployees = [];

    public ?int $selectedEmployeeId = null;

    /**
     * Carga empleados disponibles (activos y sin equipo).
     */
    public function loadAvailableEmployees(): void
    {
        $this->availableEmployees = \App\Modules\PersonnelModule\Models\Employee::active()
            ->whereDoesntHave('currentTeamMember')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn($emp) => [$emp->id => "{$emp->full_name} ({$emp->employee_number})"])
            ->toArray();
    }

    /**
     * Asigna un empleado al equipo.
     */
    public function addMember(): void
    {
        $this->authorize('update', $this->team);

        if (!$this->selectedEmployeeId) {
            return;
        }

        $action = new \App\Modules\PersonnelModule\Actions\AssignEmployeeToTeamAction;
        $action->execute(new \App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO(
            employee_id: $this->selectedEmployeeId,
            team_id: $this->team->id,
            joined_at: now()->format('Y-m-d')
        ));

        $this->selectedEmployeeId = null;
        $this->loadTeam();
        
        $this->dispatch('modal-close', name: 'add-member-modal');

        \Flux::toast(
            heading: 'Miembro añadido',
            text: 'El empleado ha sido asignado al equipo exitosamente.',
            variant: 'success'
        );
    }
    public function toggleStatus(): void
    {
        $this->authorize('update', $this->team);

        $action = new ToggleTeamStatusAction;
        $this->team = $action->execute($this->team);

        session()->flash('success',
            $this->team->is_active
            ? 'Equipo activado exitosamente.'
            : 'Equipo desactivado exitosamente.'
        );

        $this->dispatch('teamStatusToggled');
    }

    public function render()
    {
        return view('personnel::livewire.show-team')
            ->layout('layouts.app');
    }
}
