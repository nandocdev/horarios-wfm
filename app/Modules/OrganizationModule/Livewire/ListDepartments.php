<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Shared\Support\ManageCatalog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ListDepartments extends Component
{
    use ManageCatalog;

    public string $search = '';

    public int $perPage = 10;

    public ?int $directorateFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'directorateFilter' => ['except' => null],
    ];

    protected function catalogModel(): string
    {
        return Department::class;
    }

    protected function catalogLabel(): string
    {
        return 'Departamento';
    }

    public function updatedDirectorateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function getDepartmentsQuery(): Builder
    {
        return Department::query()
            ->with('directorate')
            ->when($this->search, fn (Builder $query) => $query->where('name', 'ilike', '%'.$this->search.'%')
                ->orWhere('description', 'ilike', '%'.$this->search.'%'))
            ->when($this->directorateFilter, fn (Builder $query) => $query->where('directorate_id', $this->directorateFilter))
            ->orderBy('name');
    }

    public function getDirectoratesProperty()
    {
        return Directorate::orderBy('name')->get();
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
        return view('organization::livewire.list-departments', [
            'departments' => $this->getDepartmentsQuery()->paginate($this->perPage),
        ]);
    }
}
