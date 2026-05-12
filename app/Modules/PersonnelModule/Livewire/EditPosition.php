<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\UpdatePositionAction;
use App\Modules\PersonnelModule\DTOs\PositionDTO;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Position;
use Livewire\Component;

/**
 * Componente Livewire para editar una posición existente.
 */
class EditPosition extends Component
{
    public Position $position;

    public string $name = '';

    public ?string $description = '';

    public int $department_id;

    public bool $is_active = true;

    public function mount(Position $position): void
    {
        $this->authorize('update', $position);
        $this->position = $position;

        $this->name = $position->name;
        $this->description = $position->description;
        $this->department_id = $position->department_id;
        $this->is_active = $position->is_active;
    }

    /**
     * Reglas de validación del formulario.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
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
            'description' => 'descripción',
            'department_id' => 'departamento',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Maneja el envío del formulario.
     */
    public function save()
    {
        $this->authorize('update', $this->position);

        $validated = $this->validate();

        $dto = PositionDTO::fromArray($validated);
        $action = new UpdatePositionAction;
        $this->position = $action->execute($this->position, $dto);

        session()->flash('success', 'Posición actualizada exitosamente.');

        $this->dispatch('positionUpdated', positionId: $this->position->id);

        return $this->redirect(route('organization.positions.show', $this->position));
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
        return view('personnel::livewire.edit-position')
            ->layout('layouts.app');
    }
}
