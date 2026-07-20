<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\AssignEmployeeToTeamAction;
use App\Modules\PersonnelModule\Actions\RemoveEmployeeFromTeamAction;
use App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO;
use App\Modules\PersonnelModule\DTOs\RemoveEmployeeFromTeamDTO;
use App\Modules\PersonnelModule\Livewire\Forms\TeamMemberForm;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

class ManageTeamMembers extends Component
{
    public Team $team;

    public TeamMemberForm $form;

    public bool $showAssignModal = false;

    public bool $showRemoveModal = false;

    public ?int $selectedEmployeeId = null;

    public function mount(Team $team): void
    {
        $this->authorize('update', $team);
        $this->team = $team->load(['members.employee', 'users']);
        $this->form->start_date = now()->format('Y-m-d');
        $this->form->remove_end_date = now()->format('Y-m-d');
    }

    public function openAssignModal(): void
    {
        $this->showAssignModal = true;
        $this->form->resetForAssign();
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->form->reset();
    }

    public function openRemoveModal(int $employeeId): void
    {
        $this->selectedEmployeeId = $employeeId;
        $this->showRemoveModal = true;
        $this->form->resetForRemove();
    }

    public function closeRemoveModal(): void
    {
        $this->showRemoveModal = false;
        $this->selectedEmployeeId = null;
        $this->form->reset();
    }

    public function assignEmployee(): void
    {
        $this->form->validate();

        $dto = new AssignEmployeeToTeamDTO(
            employee_id: $this->form->employee_id,
            team_id: $this->team->id,
            joined_at: $this->form->start_date,
            left_at: $this->form->end_date,
        );

        app(AssignEmployeeToTeamAction::class)->execute($dto);

        $this->team->load(['members.employee', 'users']);

        $this->dispatch('teamMembersUpdated');
        toast('Empleado asignado al equipo exitosamente.');
        $this->closeAssignModal();
    }

    public function removeEmployee(): void
    {
        $this->form->validate($this->form->removeRules());

        $dto = new RemoveEmployeeFromTeamDTO(
            employee_id: $this->selectedEmployeeId,
            team_id: $this->team->id,
            left_at: $this->form->remove_end_date,
        );

        app(RemoveEmployeeFromTeamAction::class)->execute($dto);

        $this->team->load(['members.employee', 'users']);

        $this->dispatch('teamMembersUpdated');
        toast('Empleado removido del equipo exitosamente.');
        $this->closeRemoveModal();
    }

    public function getAvailableEmployeesProperty(): mixed
    {
        return Employee::whereDoesntHave('teamMembers', fn ($q) => $q->where('is_active', true))
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
