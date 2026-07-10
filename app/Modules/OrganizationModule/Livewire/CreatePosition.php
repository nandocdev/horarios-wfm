<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\CreatePositionAction;
use App\Modules\OrganizationModule\DTOs\PositionDTO;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use Livewire\Component;

/**
 * Componente Livewire para crear una nueva posición.
 *
 * Maneja validación del formulario y delega creación a CreatePositionAction.
 */
class CreatePosition extends Component
{
    public string $name = '';

    public string $position_code = '';

    public ?string $description = '';

    public int $department_id;

    public bool $is_active = true;

    /**
     * Reglas de validación del formulario.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'position_code' => ['required', 'string', 'max:20', 'unique:positions,position_code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'position_code' => 'código de posición',
            'description' => 'descripción',
            'department_id' => 'departamento',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Maneja el envío del formulario.
     */
    public function save(): void
    {
        $this->authorize('create', Position::class);

        $validated = $this->validate();

        $dto = PositionDTO::fromArray($validated);
        $action = new CreatePositionAction;
        $position = $action->execute($dto);

        session()->flash('success', 'Posición creada exitosamente.');

        $this->dispatch('positionCreated', positionId: $position->id);

        // Resetear formulario
        $this->reset();
    }

    /**
     * Obtiene los departamentos disponibles.
     */
    public function getDepartmentsProperty()
    {
        return Department::with('directorate')->orderBy('name')->get();
    }

    public function render()
    {
        return view('organization::livewire.create-position');
    }
}
