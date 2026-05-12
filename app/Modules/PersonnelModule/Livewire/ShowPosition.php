<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\TogglePositionStatusAction;
use App\Modules\PersonnelModule\Models\Position;
use Livewire\Component;

/**
 * Componente Livewire para mostrar los detalles de una posición.
 */
class ShowPosition extends Component
{
    public Position $position;

    public function mount(Position $position): void
    {
        $this->authorize('view', $position);
        $this->position = $position->load(['department.directorate', 'users']);
    }

    /**
     * Cambia el estado activo/inactivo de la posición.
     */
    public function toggleStatus(): void
    {
        $this->authorize('update', $this->position);

        $action = new TogglePositionStatusAction;
        $this->position = $action->execute($this->position);

        session()->flash('success',
            $this->position->is_active
            ? 'Posición activada exitosamente.'
            : 'Posición desactivada exitosamente.'
        );

        $this->dispatch('positionStatusToggled');
    }

    public function render()
    {
        return view('personnel::livewire.show-position')
            ->layout('layouts.app');
    }
}
