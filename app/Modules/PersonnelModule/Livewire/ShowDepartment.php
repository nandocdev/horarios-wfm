<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\ToggleDepartmentStatusAction;
use App\Modules\PersonnelModule\Models\Department;
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
        return view('personnel::livewire.show-department')
            ->layout('layouts.app');
    }
}
