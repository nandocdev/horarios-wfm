<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\UpdateDepartmentAction;
use App\Modules\PersonnelModule\DTOs\DepartmentDTO;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Directorate;
use Livewire\Component;

/**
 * Componente Livewire para editar un departamento existente.
 */
class EditDepartment extends Component
{
    public Department $department;

    public string $name = '';

    public ?string $description = '';

    public int $directorate_id;

    public function mount(Department $department): void
    {
        $this->authorize('update', $department);
        $this->department = $department;

        $this->name = $department->name;
        $this->description = $department->description;
        $this->directorate_id = $department->directorate_id;
    }

    /**
     * Reglas de validación del formulario.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'directorate_id' => ['required', 'integer', 'exists:directorates,id'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'directorate_id' => 'dirección',
        ];
    }

    /**
     * Maneja el envío del formulario.
     */
    public function save()
    {
        $this->authorize('update', $this->department);

        $validated = $this->validate();

        $dto = DepartmentDTO::fromArray($validated);
        $action = new UpdateDepartmentAction;
        $this->department = $action->execute($this->department, $dto);

        session()->flash('success', 'Departamento actualizado exitosamente.');

        $this->dispatch('departmentUpdated', departmentId: $this->department->id);

        return $this->redirect(route('organization.departments.show', $this->department));
    }

    /**
     * Obtiene las direcciones disponibles.
     */
    public function getDirectoratesProperty()
    {
        return Directorate::orderBy('name')->get();
    }

    public function render()
    {
        return view('personnel::livewire.edit-department')
            ->layout('layouts.app');
    }
}
