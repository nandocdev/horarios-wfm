<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire para listar posiciones.
 *
 * Incluye filtros por nombre, departamento y estado, paginación y acciones básicas.
 */
class ListPositions extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public ?int $departmentFilter = null;

    public ?bool $activeFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'departmentFilter' => ['except' => null],
        'activeFilter' => ['except' => null],
    ];

    /**
     * Resetea la paginación cuando cambian los filtros.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
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
    public function getPositionsQuery(): Builder
    {
        return Position::query()
            ->with('department.directorate')
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('description', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->departmentFilter, function (Builder $query) {
                $query->where('department_id', $this->departmentFilter);
            })
            ->when($this->activeFilter !== null, function (Builder $query) {
                $query->where('is_active', $this->activeFilter);
            })
            ->orderBy('name');
    }

    /**
     * Obtiene los departamentos para el filtro.
     */
    public function getDepartmentsProperty()
    {
        return Department::orderBy('name')->get();
    }

    public function render()
    {
        $positions = $this->getPositionsQuery()
            ->withCount('users')
            ->paginate($this->perPage);

        return view('personnel::livewire.list-positions', [
            'positions' => $positions,
        ]);
    }
}
