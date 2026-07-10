<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire para listar departamentos.
 *
 * Incluye filtros por nombre y dirección, paginación y acciones básicas.
 */
class ListDepartments extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public ?int $directorateFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'directorateFilter' => ['except' => null],
    ];

    /**
     * Resetea la paginación cuando cambian los filtros.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDirectorateFilter(): void
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
    public function getDepartmentsQuery(): Builder
    {
        return Department::query()
            ->with('directorate')
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('description', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->directorateFilter, function (Builder $query) {
                $query->where('directorate_id', $this->directorateFilter);
            })
            ->orderBy('name');
    }

    /**
     * Obtiene las direcciones para el filtro.
     */
    public function getDirectoratesProperty()
    {
        return Directorate::orderBy('name')->get();
    }

    public function render()
    {
        $departments = $this->getDepartmentsQuery()->paginate($this->perPage);

        return view('organization::livewire.list-departments', [
            'departments' => $departments,
        ]);
    }
}
