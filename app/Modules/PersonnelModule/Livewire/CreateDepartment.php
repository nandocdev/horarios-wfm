<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\CreateDepartmentAction;
use App\Modules\PersonnelModule\DTOs\DepartmentDTO;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Directorate;
use Livewire\Component;

/**
 * Componente Livewire para crear un nuevo departamento.
 *
 * Maneja validación del formulario y delega creación a CreateDepartmentAction.
 */
class CreateDepartment extends Component
{
    public string $name = '';

    public ?string $description = '';

    public int $directorate_id;

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
    public function save(): void
    {
        $this->authorize('create', Department::class);

        $validated = $this->validate();

        $dto = DepartmentDTO::fromArray($validated);
        $action = new CreateDepartmentAction;
        $department = $action->execute($dto);

        session()->flash('success', 'Departamento creado exitosamente.');

        $this->dispatch('departmentCreated', departmentId: $department->id);

        // Resetear formulario
        $this->reset();
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
        return view('personnel::livewire.create-department');
    }
}
