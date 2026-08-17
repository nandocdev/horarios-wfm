<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Livewire;

use App\Modules\DirectoryModule\Models\DirectoryService;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado administrativo del directorio, una fila por servicio.
 */
class ManageDirectoryUnits extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public bool $showContactCard = false;

    public ?DirectoryService $viewingService = null;

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

    public function openContactCard(DirectoryService $service): void
    {
        $this->authorize('view', $service->unit);

        $this->viewingService = $service->load(['unit.building']);
        $this->showContactCard = true;
    }

    public function closeContactCard(): void
    {
        $this->showContactCard = false;
        $this->viewingService = null;
    }

    public function render()
    {
        $this->authorize('viewAny', Unit::class);

        $services = DirectoryService::with(['unit.building'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('door_id', 'like', '%'.$this->search.'%')
                        ->orWhere('contact_role', 'like', '%'.$this->search.'%')
                        ->orWhereHas('unit', function ($u) {
                            $u->where('sector', 'like', '%'.$this->search.'%')
                                ->orWhere('level', 'like', '%'.$this->search.'%')
                                ->orWhereHas('building', fn ($b) => $b->where('name', 'like', '%'.$this->search.'%'));
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('directory::livewire.manage-directory-units', [
            'services' => $services,
        ])->layout('layouts.app');
    }
}
