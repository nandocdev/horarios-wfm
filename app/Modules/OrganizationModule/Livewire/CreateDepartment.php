<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\CreateDepartmentAction;
use App\Modules\OrganizationModule\DTOs\DepartmentDTO;
use App\Modules\OrganizationModule\Livewire\Forms\DepartmentForm;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use Livewire\Component;

class CreateDepartment extends Component
{
    public DepartmentForm $form;

    public function save(): void
    {
        $this->authorize('create', Department::class);

        $this->form->validate();

        $dto = DepartmentDTO::fromArray([
            'directorate_id' => $this->form->directorate_id,
            'name' => $this->form->name,
            'description' => $this->form->description,
        ]);

        $action = new CreateDepartmentAction;
        $department = $action->execute($dto);

        session()->flash('success', 'Departamento creado exitosamente.');

        $this->dispatch('departmentCreated', departmentId: $department->id);

        $this->form->reset();
    }

    public function getDirectoratesProperty()
    {
        return Directorate::orderBy('name')->get();
    }

    public function render()
    {
        return view('organization::livewire.create-department');
    }
}
