<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\AssignEmployeeToTeamAction;
use App\Modules\PersonnelModule\Actions\RemoveEmployeeFromTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO;
use App\Modules\PersonnelModule\DTOs\RemoveEmployeeFromTeamDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

/**
 * Componente Livewire para gestionar los miembros de un equipo.
 */
class ManageTeamMembers extends Component
{
    public Team $team;

    public bool $showAssignModal = false;

    public bool $showRemoveModal = false;

    public ?int $selectedEmployeeId = null;

    // Form fields
    public int $employee_id = 0;

    public string $start_date = '';

    public ?string $end_date = null;

    public string $remove_end_date = '';

    public function mount(Team $team): void
    {
        $this->authorize('update', $team);
        $this->team = $team->load(['members.employee', 'users']);
        $this->start_date = now()->format('Y-m-d');
        $this->remove_end_date = now()->format('Y-m-d');
    }

    /**
     * Reglas de validación para asignar empleado.
     */
    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    protected function validationAttributes(): array
    {
        return [
            'employee_id' => 'empleado',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
        ];
    }

    /**
     * Abre el modal para asignar empleado.
     */
    public function openAssignModal(): void
    {
        $this->showAssignModal = true;
        $this->resetValidation();
        $this->employee_id = 0;
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = null;
    }

    /**
     * Cierra el modal de asignación.
     */
    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->resetValidation();
    }

    /**
     * Abre el modal para remover empleado.
     */
    public function openRemoveModal(int $employeeId): void
    {
        $this->selectedEmployeeId = $employeeId;
        $this->showRemoveModal = true;
        $this->remove_end_date = now()->format('Y-m-d');
    }

    /**
     * Cierra el modal de remoción.
     */
    public function closeRemoveModal(): void
    {
        $this->showRemoveModal = false;
        $this->selectedEmployeeId = null;
    }

    /**
     * Asigna un empleado al equipo.
     */
    public function assignEmployee(): void
    {
        $this->validate();

        $dto = new AssignEmployeeToTeamDTO(
            employee_id: $this->employee_id,
            team_id: $this->team->id,
            joined_at: $this->start_date,
            left_at: $this->end_date,
        );

        $action = new AssignEmployeeToTeamAction;
        $action->execute($dto);

        $this->team->load(['members.employee', 'users']);

        session()->flash('success', 'Empleado asignado al equipo exitosamente.');

        $this->closeAssignModal();

        $this->dispatch('teamMembersUpdated');
    }

    /**
     * Remueve un empleado del equipo.
     */
    public function removeEmployee(): void
    {
        $this->validate([
            'remove_end_date' => ['required', 'date', 'after_or_equal:today'],
        ], [], [
            'remove_end_date' => 'fecha de fin',
        ]);

        $dto = new RemoveEmployeeFromTeamDTO(
            employee_id: $this->selectedEmployeeId,
            team_id: $this->team->id,
            left_at: $this->remove_end_date,
        );

        $action = new RemoveEmployeeFromTeamAction;
        $action->execute($dto);

        $this->team->load(['members.employee', 'users']);

        session()->flash('success', 'Empleado removido del equipo exitosamente.');

        $this->closeRemoveModal();

        $this->dispatch('teamMembersUpdated');
    }

    /**
     * Obtiene empleados disponibles para asignar (no están en equipos activos).
     */
    public function getAvailableEmployeesProperty(): mixed
    {
        return Employee::whereDoesntHave('teamMembers', function ($query) {
            $query->where('is_active', true);
        })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function render()
    {
        return view('personnel::livewire.manage-team-members', [
            'availableEmployees' => $this->availableEmployees,
        ])
            ->layout('layouts.app');
    }
}
