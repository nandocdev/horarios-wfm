<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\News;
use App\Src\Platform\Application\Handlers\DeleteNewsHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Noticias')]
class ListNews extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function deleteNews(int $id): void
    {
        $handler = app(DeleteNewsHandler::class);
        $handler->execute($id);

        $this->dispatch('news-deleted');
    }

    public function render()
    {
        $query = News::with('author');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('content', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('platform::livewire.list-news', [
            'news' => $query->latest()->paginate(15),
        ]);
    }
}
