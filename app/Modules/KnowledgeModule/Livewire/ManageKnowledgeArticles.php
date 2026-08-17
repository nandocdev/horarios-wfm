<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Livewire;

use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use App\Modules\KnowledgeModule\Models\Queue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire administrativo para gestionar la creación, edición y flujo editorial de los artículos.
 */
class ManageKnowledgeArticles extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $selectedStatus = '';

    public ?int $selectedCategory = null;

    /**
     * Listener para actualizar listados cuando se limpian filtros.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Elimina físicamente un artículo.
     */
    public function deleteArticle(KnowledgeArticle $article): void
    {
        $this->authorize('delete', $article);
        $article->delete();
        \Flux::toast('Artículo eliminado correctamente.');
        $this->resetPage();
    }

    /**
     * Renderiza la vista de administración.
     */
    public function render()
    {
        $this->authorize('viewAny', KnowledgeArticle::class);

        $categories = KnowledgeCategory::orderBy('name')->get();
        $queues = Queue::where('is_active', true)->orderBy('name')->get();

        $articles = KnowledgeArticle::with(['category', 'queues', 'creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->selectedStatus, function ($query) {
                $query->where('status', $this->selectedStatus);
            })
            ->when($this->selectedCategory, function ($query) {
                $query->where('category_id', $this->selectedCategory);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('knowledge::livewire.manage-knowledge-articles', [
            'articles' => $articles,
            'categories' => $categories,
            'queues' => $queues,
        ])->layout('layouts.app');
    }
}
