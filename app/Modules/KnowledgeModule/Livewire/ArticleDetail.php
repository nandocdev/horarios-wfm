<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Livewire;

use App\Modules\KnowledgeModule\Models\Article;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Componente Livewire para la lectura a detalle de una publicación de la Base de Conocimiento,
 * incluyendo histórico de versiones y etiquetas relacionadas.
 */
class ArticleDetail extends Component
{
    use AuthorizesRequests;

    public Article $article;

    /**
     * Inicializa el componente recuperando el artículo por slug con relaciones cargadas.
     */
    public function mount(string $slug): void
    {
        $this->article = Article::with(['category', 'queues', 'tags', 'creator', 'versions.creator'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->authorize('view', $this->article);
    }

    /**
     * Renderiza la vista de detalle.
     */
    public function render()
    {
        return view('knowledge::livewire.article-detail', [
            'article' => $this->article,
        ])->layout('layouts.app');
    }
}
/**
 * [RIESGOS]
 * - Fugas de Información → Se debe proteger la ruta en la Policy para asegurar que artículos en estado "Draft" o "Review" solo sean vistos por supervisores.
 */
