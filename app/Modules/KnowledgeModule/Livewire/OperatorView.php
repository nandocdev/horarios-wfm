<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Livewire;

use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use App\Modules\KnowledgeModule\Models\Queue;
use App\Modules\KnowledgeModule\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Componente Livewire que controla la interfaz de búsqueda y lectura interactiva para el operador de atención.
 */
class OperatorView extends Component
{
    use AuthorizesRequests;

    public $selectedQueueId = null;

    public $selectedCategoryId = null;

    public $search = '';

    public $selectedTag = '';

    /**
     * Resetea la paginación u otros filtros al cambiar de cola.
     */
    public function selectQueue($queueId): void
    {
        $this->selectedQueueId = $queueId ? (int) $queueId : null;
        $this->reset(['selectedCategoryId', 'selectedTag']);
    }

    /**
     * Resetea todos los filtros aplicados.
     */
    public function resetFilters(): void
    {
        $this->reset(['selectedQueueId', 'selectedCategoryId', 'search', 'selectedTag']);
    }

    /**
     * Renderiza la vista del operador.
     */
    public function render()
    {
        $this->authorize('viewAny', KnowledgeArticle::class);

        // Cargar catálogos
        $queues = Queue::where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        $categories = KnowledgeCategory::orderBy('id')->get();

        $tags = Tag::whereHas('articles', function ($query) {
            $query->published();
        })
            ->take(15)
            ->get();

        // Consulta de artículos
        $query = KnowledgeArticle::published()
            ->with(['category', 'queues', 'tags'])
            ->withMax('queues', 'priority');

        if ($this->selectedQueueId) {
            $query->whereHas('queues', function ($q) {
                $q->where('knowledge_queues.id', $this->selectedQueueId);
            });
        }

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        if ($this->selectedTag) {
            $query->whereHas('tags', function ($q) {
                $q->where('knowledge_tags.name', $this->selectedTag);
            });
        }

        if (! empty($this->search)) {
            $searchVal = '%'.$this->search.'%';
            $query->where(function ($q) use ($searchVal) {
                $q->where('title', 'like', $searchVal)
                    ->orWhere('summary', 'like', $searchVal)
                    ->orWhere('content', 'like', $searchVal)
                    ->orWhereHas('tags', function ($tagQ) use ($searchVal) {
                        $tagQ->where('name', 'like', $searchVal);
                    });
            });
        }

        // Ordenar por prioridad calculada (MAX de prioridades de cola)
        $query->orderByQueuePriority('desc')->orderBy('title', 'asc');

        $articles = $query->get();

        // Si hay una cola seleccionada y no se está buscando texto libre, agrupamos por categoría.
        // Esto cumple la regla: "Cuando el operador selecciona una cola: agrupar por categoría".
        $groupedArticles = null;
        if ($this->selectedQueueId && empty($this->search)) {
            $groupedArticles = $articles->groupBy(function ($art) {
                return $art->category ? $art->category->name : 'General';
            });
        }

        return view('knowledge::livewire.operator-view', [
            'queues' => $queues,
            'categories' => $categories,
            'tags' => $tags,
            'articles' => $articles,
            'groupedArticles' => $groupedArticles,
            'selectedQueue' => $this->selectedQueueId ? Queue::find($this->selectedQueueId) : null,
        ])->layout('layouts.app');
    }
}
/**
 * [RIESGOS]
 * - N+1 Query en Relaciones → Resuelto cargando ansiosamente ('with') category, queues y tags en la consulta base.
 * - Escalabilidad bajo carga → Si el número de artículos crece exponencialmente, el uso de ->get() sin paginación podría saturar memoria. Para el operador en producción, se asume un catálogo controlado (< 500 artículos por cola).
 */
