<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\Poll;
use App\Src\Platform\Application\Handlers\ArchivePollHandler;
use App\Src\Platform\Application\Handlers\DeletePollHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Encuestas')]
class ListPolls extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatedSearch(): void { $this->resetPage(); }

    public function archivePoll(int $id): void
    {
        $handler = app(ArchivePollHandler::class);
        $handler->execute($id);

        $this->dispatch('poll-archived');
    }

    public function deletePoll(int $id): void
    {
        $handler = app(DeletePollHandler::class);
        $handler->execute($id);

        $this->dispatch('poll-deleted');
    }

    public function render()
    {
        $query = Poll::withCount('responses');

        if ($this->search) {
            $query->where('question', 'like', "%{$this->search}%");
        }

        return view('platform::livewire.list-polls', [
            'polls' => $query->latest()->paginate(15),
        ]);
    }
}
