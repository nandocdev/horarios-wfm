<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire;

use App\Modules\OrganizationModule\Models\Directorate;
use App\Shared\Support\ManageCatalog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ListDirectorates extends Component
{
    use ManageCatalog;

    public string $search = '';

    public int $perPage = 10;

    public ?bool $activeFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'activeFilter' => ['except' => null],
    ];

    protected function catalogModel(): string
    {
        return Directorate::class;
    }

    protected function catalogLabel(): string
    {
        return 'Dirección';
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function getDirectoratesQuery(): Builder
    {
        return Directorate::query()
            ->when($this->search, fn (Builder $query) => $query->where('name', 'ilike', '%'.$this->search.'%')
                ->orWhere('description', 'ilike', '%'.$this->search.'%'))
            ->when($this->activeFilter !== null, fn (Builder $query) => $query->where('is_active', $this->activeFilter))
            ->orderBy('name');
    }

    protected function resetForm(): void
    {
        // No inline form — resets handled by separate CRUD routes
    }

    protected function loadForm(object $record): void
    {
        // No inline form — editing handled by separate CRUD routes
    }

    public function render()
    {
        return view('organization::livewire.list-directorates', [
            'directorates' => $this->getDirectoratesQuery()->paginate($this->perPage),
        ]);
    }
}
