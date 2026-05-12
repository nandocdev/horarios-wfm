<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Models\News;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de noticias en el panel de administración.
 */
class ListNews extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Elimina una noticia.
     */
    public function deleteNews(int $id): void
    {
        $news = News::findOrFail($id);
        $this->authorize('delete', $news);

        $news->delete();
        toast('Noticia eliminada correctamente.');
    }

    /**
     * Renderizado con paginación.
     */
    public function render()
    {
        return view('communications::livewire.list-news', [
            'news' => News::with('author')
                ->where(function ($q) {
                    $q->where('title', 'ilike', "%{$this->search}%")
                        ->orWhere('excerpt', 'ilike', "%{$this->search}%");
                })
                ->latest('published_at')
                ->paginate(10),
        ]);
    }
}
