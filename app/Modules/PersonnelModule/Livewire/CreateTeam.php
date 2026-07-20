<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\CreateTeamAction;
use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Livewire\Forms\TeamForm;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

class CreateTeam extends Component
{
    public TeamForm $form;

    public function save(): void
    {
        $this->authorize('create', Team::class);

        $this->form->validate();

        $dto = TeamDTO::fromArray([
            'name' => $this->form->name,
            'description' => $this->form->description,
            'supervisor_id' => $this->form->supervisor_id,
            'cisco_team_id' => $this->form->cisco_team_id,
            'is_active' => $this->form->is_active,
        ]);

        $action = new CreateTeamAction;
        $team = $action->execute($dto);

        session()->flash('success', 'Equipo creado exitosamente.');
        $this->dispatch('teamCreated', teamId: $team->id);

        $this->form->reset();
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
        return view('personnel::livewire.create-team');
    }
}
