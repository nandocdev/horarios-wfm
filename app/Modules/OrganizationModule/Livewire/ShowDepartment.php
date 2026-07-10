<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Actions\ToggleDepartmentStatusAction;
use App\Modules\OrganizationModule\Models\Department;
use Livewire\Component;

/**
 * Componente Livewire para mostrar los detalles de un departamento.
 */
class ShowDepartment extends Component
{
    public Department $department;

    public function mount(Department $department): void
    {
        $this->authorize('view', $department);
        $this->department = $department->load(['directorate', 'positions']);
    }

    /**
     * Cambia el estado activo/inactivo del departamento.
     */
    public function toggleStatus(): void
    {
        $this->authorize('update', $this->department);

        $action = new ToggleDepartmentStatusAction;
        $this->department = $action->execute($this->department);

        session()->flash('success',
            $this->department->is_active
            ? 'Departamento activado exitosamente.'
            : 'Departamento desactivado exitosamente.'
        );

        $this->dispatch('departmentStatusToggled');
    }

    public function render()
    {
        return view('organization::livewire.show-department')
            ->layout('layouts.app');
    }
}
