<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use App\Shared\Support\ManageCatalog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ListPositions extends Component
{
    use ManageCatalog;

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

    protected function catalogModel(): string
    {
        return Position::class;
    }

    protected function catalogLabel(): string
    {
        return 'Posición';
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

    public function getPositionsQuery(): Builder
    {
        return Position::query()
            ->with('department.directorate')
            ->when($this->search, fn (Builder $query) => $query->where('name', 'ilike', '%'.$this->search.'%')
                ->orWhere('description', 'ilike', '%'.$this->search.'%'))
            ->when($this->departmentFilter, fn (Builder $query) => $query->where('department_id', $this->departmentFilter))
            ->when($this->activeFilter !== null, fn (Builder $query) => $query->where('is_active', $this->activeFilter))
            ->orderBy('name');
    }

    public function getDepartmentsProperty()
    {
        return Department::orderBy('name')->get();
    }

    protected function resetForm(): void
    {
        // No inline form
    }

    protected function loadForm(object $record): void
    {
        // No inline form
    }

    public function render()
    {
        return view('organization::livewire.list-positions', [
            'positions' => $this->getPositionsQuery()->paginate($this->perPage),
        ]);
    }
}
