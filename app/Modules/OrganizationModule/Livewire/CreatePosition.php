<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\CreatePositionAction;
use App\Modules\OrganizationModule\DTOs\PositionDTO;
use App\Modules\OrganizationModule\Livewire\Forms\PositionForm;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use Livewire\Component;

class CreatePosition extends Component
{
    public PositionForm $form;

    public function save(): void
    {
        $this->authorize('create', Position::class);

        $this->form->validate();

        $dto = PositionDTO::fromArray([
            'department_id' => $this->form->department_id,
            'name' => $this->form->name,
            'position_code' => $this->form->position_code,
            'description' => $this->form->description,
            'is_active' => $this->form->is_active,
            'salary' => $this->form->salary,
        ]);

        $action = new CreatePositionAction;
        $position = $action->execute($dto);

        session()->flash('success', 'Posición creada exitosamente.');

        $this->dispatch('positionCreated', positionId: $position->id);

        $this->form->reset();
    }

    public function getDepartmentsProperty()
    {
        return Department::with('directorate')->orderBy('name')->get();
    }

    public function render()
    {
        return view('organization::livewire.create-position');
    }
}
