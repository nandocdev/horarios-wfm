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

        $search = trim($this->search);

        $services = DirectoryService::with(['unit.building', 'phones'])
            ->when($search !== '', function ($query) use ($search) {
                // FTS con ranking + fallback LIKE para compat
                $isNumeric = preg_match('/^[\d\-]+$/', $search) === 1;

                if ($isNumeric) {
                    // Búsqueda por número: trigram + exact
                    $query->where(function ($q) use ($search) {
                        $q->where('contact_extension', 'like', '%'.$search.'%')
                            ->orWhere('door_id', 'like', '%'.$search.'%')
                            ->orWhereHas('phones', fn ($ph) => $ph->where('number', 'like', '%'.$search.'%'))
                            ->orWhereHas('unit.phones', fn ($ph) => $ph->where('number', 'like', '%'.$search.'%'));
                    });
                } else {
                    // Texto: FTS español con ranking, fallback a LIKE
                    $query->where(function ($q) use ($search) {
                        $q->whereRaw("tsv_service @@ plainto_tsquery('spanish', ?)", [$search])
                            ->orWhere('name', 'ilike', '%'.$search.'%')
                            ->orWhere('contact_role', 'ilike', '%'.$search.'%')
                            ->orWhere('door_id', 'ilike', '%'.$search.'%')
                            ->orWhereHas('unit', function ($u) use ($search) {
                                $u->where('sector', 'ilike', '%'.$search.'%')
                                    ->orWhere('level', 'ilike', '%'.$search.'%')
                                    ->orWhere('door_range', 'ilike', '%'.$search.'%')
                                    ->orWhereHas('building', fn ($b) => $b->where('name', 'ilike', '%'.$search.'%')
                                        ->orWhere('color_identifier', 'ilike', '%'.$search.'%'));
                            });
                    });
                    // Ranking FTS primero
                    $query->orderByRaw("ts_rank(tsv_service, plainto_tsquery('spanish', ?)) DESC", [$search]);
                }
            })
            ->when(empty($search) || preg_match('/^[\d\-]+$/', $search) !== 1, function ($q) {
                // ya ordenado por rank si es texto, sino por id
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('directory::livewire.manage-directory-units', [
            'services' => $services,
        ])->layout('layouts.app');
    }
}
