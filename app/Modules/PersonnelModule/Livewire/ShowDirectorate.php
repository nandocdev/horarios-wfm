<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\ToggleDirectorateStatusAction;
use App\Modules\PersonnelModule\Models\Directorate;
use Livewire\Component;

/**
 * Componente Livewire para mostrar los detalles de una dirección.
 */
class ShowDirectorate extends Component
{
    public Directorate $directorate;

    public function mount(Directorate $directorate): void
    {
        $this->authorize('view', $directorate);
        $this->directorate = $directorate->load('departments');
    }

    /**
     * Cambia el estado activo/inactivo de la dirección.
     */
    public function toggleStatus(): void
    {
        $this->authorize('update', $this->directorate);

        $action = new ToggleDirectorateStatusAction;
        $this->directorate = $action->execute($this->directorate);

        session()->flash('success',
            $this->directorate->is_active
            ? 'Dirección activada exitosamente.'
            : 'Dirección desactivada exitosamente.'
        );

        $this->dispatch('directorateStatusToggled');
    }

    public function render()
    {
        return view('personnel::livewire.show-directorate')
            ->layout('layouts.app');
    }
}
