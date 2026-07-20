<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\UpdatePositionAction;
use App\Modules\OrganizationModule\DTOs\PositionDTO;
use App\Modules\OrganizationModule\Livewire\Forms\PositionForm;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use Livewire\Component;

class EditPosition extends Component
{
    public PositionForm $form;

    public Position $position;

    public function mount(Position $position): void
    {
        $this->authorize('update', $position);
        $this->position = $position;

        $this->form->fill([
            'name' => $position->name,
            'position_code' => $position->position_code,
            'description' => $position->description,
            'department_id' => $position->department_id,
            'is_active' => (bool) $position->is_active,
            'salary' => $position->salary,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->position);

        $this->form->validate();

        $dto = PositionDTO::fromArray([
            'department_id' => $this->form->department_id,
            'name' => $this->form->name,
            'position_code' => $this->form->position_code,
            'description' => $this->form->description,
            'is_active' => $this->form->is_active,
            'salary' => $this->form->salary,
        ]);

        $action = new UpdatePositionAction;
        $this->position = $action->execute($this->position, $dto);

        session()->flash('success', 'Posición actualizada exitosamente.');

        $this->dispatch('positionUpdated', positionId: $this->position->id);

        return $this->redirect(route('organization.positions.show', $this->position));
    }

    public function getDepartmentsProperty()
    {
        return Department::with('directorate')->orderBy('name')->get();
    }

    public function render()
    {
        return view('organization::livewire.edit-position')
            ->layout('layouts.app');
    }
}
