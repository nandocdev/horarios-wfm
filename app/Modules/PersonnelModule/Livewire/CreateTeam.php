<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\CreateTeamAction;
use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

/**
 * Componente Livewire para crear un nuevo equipo.
 *
 * Maneja validación del formulario y delega creación a CreateTeamAction.
 */
class CreateTeam extends Component
{
    public string $name = '';

    public ?string $description = '';

    public ?int $supervisor_id = null;

    public bool $is_active = true;

    /**
     * Reglas de validación del formulario.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'supervisor_id' => ['nullable', 'exists:employees,id'],
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
            'supervisor_id' => 'supervisor',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Maneja el envío del formulario.
     */
    public function save(): void
    {
        $this->authorize('create', Team::class);

        $validated = $this->validate();

        $dto = TeamDTO::fromArray($validated);
        $action = new CreateTeamAction;
        $team = $action->execute($dto);

        session()->flash('success', 'Equipo creado exitosamente.');

        $this->dispatch('teamCreated', teamId: $team->id);

        // Resetear formulario
        $this->reset();
    }

    /**
     * Obtiene la lista de empleados disponibles como supervisores.
     */
    public function getAvailableSupervisorsProperty()
    {
        return Employee::where('is_active', true)
            ->where('is_manager', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_number']);
    }

    public function render()
    {
        return view('personnel::livewire.create-team');
    }
}
