<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire\Forms;

use App\Modules\CommunicationsModule\DTOs\NewsDTO;
use App\Modules\CommunicationsModule\Models\News;
use Livewire\Form;
use Livewire\WithFileUploads;

/**
 * Formulario para el manejo de noticias.
 */
class NewsForm extends Form
{
    use WithFileUploads;

    public ?News $newsModel = null;

    public string $title = '';

    public string $slug = '';

    public ?string $excerpt = '';

    public string $content = '';

    public string $published_at = '';

    public ?string $scheduled_at = null;

    public ?string $archive_at = null;

    /** @var int[] */
    public array $category_ids = [];

    /** @var int[] */
    public array $tag_ids = [];

    public string $workflow_action = 'save_draft';

    public bool $is_active = true;

    // Media
    public $featured_image;

    public $attachments = [];

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:news,slug,'.($this->newsModel?->id ?? 'NULL'),
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'published_at' => 'required|date',
            'scheduled_at' => 'nullable|date',
            'archive_at' => 'nullable|date|after:scheduled_at',
            'category_ids' => 'array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'workflow_action' => 'required|string|in:save_draft,submit_review',
            'is_active' => 'boolean',
            'featured_image' => 'nullable|image|max:2048', // 2MB max
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ];
    }

    /**
     * Carga datos del modelo al formulario.
     */
    public function setNews(News $news): void
    {
        $this->newsModel = $news;
        $this->title = $news->title;
        $this->slug = $news->slug;
        $this->excerpt = $news->excerpt;
        $this->content = $news->content;
        $this->published_at = $news->published_at->format('Y-m-d\TH:i');
        $this->scheduled_at = $news->scheduled_at?->format('Y-m-d\TH:i');
        $this->archive_at = $news->archive_at?->format('Y-m-d\TH:i');
        $this->category_ids = $news->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
        $this->tag_ids = $news->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
        $this->workflow_action = 'save_draft';
        $this->is_active = (bool) $news->is_active;
    }

    /**
     * Limpia el formulario.
     */
    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset([
            'title',
            'slug',
            'excerpt',
            'content',
            'published_at',
            'scheduled_at',
            'archive_at',
            'category_ids',
            'tag_ids',
            'workflow_action',
            'is_active',
            'featured_image',
            'attachments',
        ]);
        $this->workflow_action = 'save_draft';
        $this->newsModel = null;
    }

    /**
     * Convierte el formulario a un DTO inmutable.
     */
    public function toDTO(): NewsDTO
    {
        return new NewsDTO(
            title: $this->title,
            slug: $this->slug,
            excerpt: $this->excerpt,
            content: $this->content,
            published_at: $this->published_at,
            scheduled_at: $this->scheduled_at,
            archive_at: $this->archive_at,
            categoryIds: array_map('intval', $this->category_ids),
            tagIds: array_map('intval', $this->tag_ids),
            workflowAction: $this->workflow_action,
            is_active: $this->is_active,
            author_id: (int) auth()->id(),
            featuredImage: $this->featured_image,
            attachments: $this->attachments
        );
    }
}
