<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\UpdateDepartmentAction;
use App\Modules\OrganizationModule\DTOs\DepartmentDTO;
use App\Modules\OrganizationModule\Livewire\Forms\DepartmentForm;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use Livewire\Component;

class EditDepartment extends Component
{
    public DepartmentForm $form;

    public Department $department;

    public function mount(Department $department): void
    {
        $this->authorize('update', $department);
        $this->department = $department;

        $this->form->fill([
            'name' => $department->name,
            'description' => $department->description,
            'directorate_id' => $department->directorate_id,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->department);

        $this->form->validate();

        $dto = DepartmentDTO::fromArray([
            'directorate_id' => $this->form->directorate_id,
            'name' => $this->form->name,
            'description' => $this->form->description,
        ]);

        $action = new UpdateDepartmentAction;
        $this->department = $action->execute($this->department, $dto);

        session()->flash('success', 'Departamento actualizado exitosamente.');

        $this->dispatch('departmentUpdated', departmentId: $this->department->id);

        return $this->redirect(route('organization.departments.show', $this->department));
    }

    public function getDirectoratesProperty()
    {
        return Directorate::orderBy('name')->get();
    }

    public function render()
    {
        return view('organization::livewire.edit-department')
            ->layout('layouts.app');
    }
}
