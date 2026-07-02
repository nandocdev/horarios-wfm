<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Src\Platform\Application\Handlers\DeleteShoutoutHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Reconocimientos')]
class ListShoutouts extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatedSearch(): void { $this->resetPage(); }

    public function deleteShoutout(int $id): void
    {
        $handler = app(DeleteShoutoutHandler::class);
        $handler->execute($id);

        $this->dispatch('shoutout-deleted');
    }

    public function render()
    {
        $query = Shoutout::with('employee');

        if ($this->search) {
            $query->where('message', 'like', "%{$this->search}%");
        }

        return view('platform::livewire.list-shoutouts', [
            'shoutouts' => $query->latest()->paginate(15),
        ]);
    }
}
