<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire para listar direcciones.
 *
 * Incluye filtros por nombre y estado, paginación y acciones básicas.
 */
class ListDirectorates extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public ?bool $activeFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'activeFilter' => ['except' => null],
    ];

    /**
     * Resetea la paginación cuando cambian los filtros.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Obtiene la consulta base con filtros aplicados.
     */
    public function getDirectoratesQuery(): Builder
    {
        return Directorate::query()
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('description', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->activeFilter !== null, function (Builder $query) {
                $query->where('is_active', $this->activeFilter);
            })
            ->orderBy('name');
    }

    public function render()
    {
        $directorates = $this->getDirectoratesQuery()->paginate($this->perPage);

        return view('organization::livewire.list-directorates', [
            'directorates' => $directorates,
        ]);
    }
}
