<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Livewire;

use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado administrativo de unidades del directorio.
 */
class ManageDirectoryUnits extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public bool $showContactCard = false;

    public ?Unit $viewingUnit = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteUnit(Unit $unit): void
    {
        $this->authorize('delete', $unit);
        $unit->delete();
        \Flux::toast('Unidad eliminada correctamente.');
        $this->resetPage();
    }

    public function openContactCard(Unit $unit): void
    {
        $this->authorize('view', $unit);

        $this->viewingUnit = $unit->load(['building', 'services']);
        $this->showContactCard = true;
    }

    public function closeContactCard(): void
    {
        $this->showContactCard = false;
        $this->viewingUnit = null;
    }

    public function render()
    {
        $this->authorize('viewAny', Unit::class);

        $units = Unit::with('building')
            ->withCount('services')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('sector', 'like', '%'.$this->search.'%')
                        ->orWhere('level', 'like', '%'.$this->search.'%')
                        ->orWhereHas('building', fn ($b) => $b->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('services', function ($s) {
                            $s->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('door_id', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('directory::livewire.manage-directory-units', [
            'units' => $units,
        ])->layout('layouts.app');
    }
}
