<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Livewire\Forms;

use App\Modules\KnowledgeModule\DTOs\ArticleDTO;
use App\Modules\KnowledgeModule\Models\Article;
use Livewire\Form;

/**
 * Formulario de Livewire para encapsular la validación y el estado de la UI al gestionar artículos.
 */
class ArticleForm extends Form
{
    public ?Article $article = null;

    public string $title = '';

    public string $summary = '';

    public string $content = '';

    public ?int $category_id = null;

    public string $status = 'draft';

    public ?string $published_at = null;

    public ?string $expires_at = null;

    public array $queues = [];

    public string $tagsString = '';

    /**
     * Reglas de validación aplicadas en el backend.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'category_id' => 'nullable|integer|exists:knowledge_categories,id',
            'status' => 'required|string|in:draft,review,published,archived',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:published_at',
            'queues' => 'required|array|min:1',
            'queues.*' => 'integer|exists:knowledge_queues,id',
            'tagsString' => 'nullable|string',
        ];
    }

    /**
     * Nombres de atributos personalizados para mostrar errores limpios.
     */
    public function validationAttributes(): array
    {
        return [
            'title' => 'título',
            'summary' => 'resumen',
            'content' => 'contenido',
            'category_id' => 'categoría',
            'status' => 'estado',
            'published_at' => 'fecha de publicación',
            'expires_at' => 'fecha de expiración',
            'queues' => 'colas de atención',
            'tagsString' => 'etiquetas',
        ];
    }

    /**
     * Carga el estado del formulario a partir de un artículo existente para edición.
     */
    public function setArticle(Article $article): void
    {
        $this->article = $article;
        $this->title = $article->title;
        $this->summary = $article->summary ?? '';
        $this->content = $article->content;
        $this->category_id = $article->category_id;
        $this->status = $article->status;
        $this->published_at = $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : null;
        $this->expires_at = $article->expires_at ? $article->expires_at->format('Y-m-d\TH:i') : null;
        $this->queues = $article->queues->pluck('id')->toArray();
        $this->tagsString = implode(', ', $article->tags->pluck('name')->toArray());
    }

    /**
     * Convierte los datos validados del formulario en un DTO inmutable.
     */
    public function toDTO(): ArticleDTO
    {
        $tags = array_filter(
            array_map('trim', explode(',', $this->tagsString))
        );

        return ArticleDTO::fromArray([
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => $this->content,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'queues' => $this->queues,
            'tags' => $tags,
        ]);
    }

    /**
     * Limpia el formulario a su estado original para la creación.
     */
    public function resetForm(): void
    {
        $this->article = null;
        $this->title = '';
        $this->summary = '';
        $this->content = '';
        $this->category_id = null;
        $this->status = 'draft';
        $this->published_at = null;
        $this->expires_at = null;
        $this->queues = [];
        $this->tagsString = '';
    }
}
/**
 * [RIESGOS]
 * - Manipulación de arrays de colas vacíos → Las reglas de validación exigen al menos una cola (`required|array|min:1`).
 */
