<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\UpdateTeamAction;
use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Livewire\Forms\TeamForm;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

class EditTeam extends Component
{
    public TeamForm $form;

    public Team $team;

    public function mount(Team $team): void
    {
        $this->authorize('update', $team);
        $this->team = $team;

        $this->form->fill([
            'name' => $team->name,
            'description' => $team->description,
            'supervisor_id' => $team->supervisor_id,
            'cisco_team_id' => $team->cisco_team_id,
            'is_active' => (bool) $team->is_active,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->team);

        $this->form->validate();

        $dto = TeamDTO::fromArray([
            'name' => $this->form->name,
            'description' => $this->form->description,
            'supervisor_id' => $this->form->supervisor_id,
            'cisco_team_id' => $this->form->cisco_team_id,
            'is_active' => $this->form->is_active,
        ]);

        $action = new UpdateTeamAction;
        $this->team = $action->execute($this->team, $dto);

        session()->flash('success', 'Equipo actualizado exitosamente.');
        $this->dispatch('teamUpdated', teamId: $this->team->id);

        return $this->redirect(route('organization.teams.show', $this->team));
    }

    public function getAvailableSupervisorsProperty()
    {
        return Employee::where('is_active', true)
            ->where('is_manager', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_number']);
    }

    public function render()
    {
        return view('personnel::livewire.edit-team');
    }
}
