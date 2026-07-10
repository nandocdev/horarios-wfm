<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\UpdateDirectorateAction;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Models\Directorate;
use Livewire\Component;

/**
 * Componente Livewire para editar una dirección existente.
 */
class EditDirectorate extends Component
{
    public Directorate $directorate;

    public string $name = '';

    public ?string $description = '';

    public bool $is_active = true;

    public function mount(Directorate $directorate): void
    {
        $this->authorize('update', $directorate);
        $this->directorate = $directorate;

        $this->name = $directorate->name;
        $this->description = $directorate->description;
        $this->is_active = (bool) $directorate->is_active;
    }

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
    public function save()
    {
        $this->authorize('update', $this->directorate);

        $validated = $this->validate();

        $dto = DirectorateDTO::fromArray($validated);
        $action = new UpdateDirectorateAction;
        $this->directorate = $action->execute($this->directorate, $dto);

        session()->flash('success', 'Dirección actualizada exitosamente.');

        $this->dispatch('directorateUpdated', directorateId: $this->directorate->id);

        return $this->redirect(route('organization.directorates.show', $this->directorate));
    }

    public function render()
    {
        return view('organization::livewire.edit-directorate')
            ->layout('layouts.app');
    }
}
