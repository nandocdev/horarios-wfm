<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Actions\UpdateTeamAction;
use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Models\Team;
use Livewire\Component;

/**
 * Componente Livewire para editar un equipo existente.
 */
class EditTeam extends Component
{
    public Team $team;

    public string $name = '';

    public ?string $description = '';

    public ?int $supervisor_id = null;

    public bool $is_active = true;

    public function mount(Team $team): void
    {
        $this->authorize('update', $team);
        $this->team = $team;

        $this->name = $team->name;
        $this->description = $team->description;
        $this->supervisor_id = $team->supervisor_id;
        $this->is_active = $team->is_active;
    }

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
    public function save()
    {
        $this->authorize('update', $this->team);

        $validated = $this->validate();

        $dto = TeamDTO::fromArray($validated);
        $action = new UpdateTeamAction;
        $this->team = $action->execute($this->team, $dto);

        session()->flash('success', 'Equipo actualizado exitosamente.');

        $this->dispatch('teamUpdated', teamId: $this->team->id);

        return $this->redirect(route('organization.teams.show', $this->team));
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
        return view('personnel::livewire.edit-team');
    }
}
