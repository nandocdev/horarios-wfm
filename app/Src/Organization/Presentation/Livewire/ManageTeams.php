<?php

declare(strict_types=1);

namespace App\Src\Organization\Presentation\Livewire;

use App\Src\Organization\Infrastructure\Persistence\EloquentTeam;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Gestión de Equipos')]
class ManageTeams extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = EloquentTeam::with('members')
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"));

        return view('organization::livewire.manage-teams', [
            'teams' => $query->latest()->paginate(10),
        ]);
    }
}
