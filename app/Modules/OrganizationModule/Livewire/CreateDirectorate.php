<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\CreateDirectorateAction;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Models\Directorate;
use Livewire\Component;

/**
 * Componente Livewire para crear una nueva dirección.
 *
 * Maneja validación del formulario y delega creación a CreateDirectorateAction.
 */
class CreateDirectorate extends Component
{
    public string $name = '';

    public ?string $description = '';

    public bool $is_active = true;

    /**
     * Reglas de validación del formulario.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Maneja el envío del formulario.
     */
    public function save(): void
    {
        $this->authorize('create', Directorate::class);

        $validated = $this->validate();

        $dto = DirectorateDTO::fromArray($validated);
        $action = new CreateDirectorateAction;
        $directorate = $action->execute($dto);

        session()->flash('success', 'Dirección creada exitosamente.');

        $this->dispatch('directorateCreated', directorateId: $directorate->id);

        // Resetear formulario
        $this->reset();
    }

    public function render()
    {
        return view('organization::livewire.create-directorate');
    }
}
