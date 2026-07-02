<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Tag;
use App\Src\Platform\Application\DTOs\NewsDTO;
use App\Src\Platform\Application\Handlers\UpdateNewsHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Editar Noticia')]
class EditNews extends Component
{
    use WithFileUploads;

    public News $news;
    public string $mode = 'edit';

    public array $form = [
        'title' => '',
        'slug' => '',
        'excerpt' => '',
        'content' => '',
        'category_ids' => [],
        'tag_ids' => [],
        'published_at' => '',
        'scheduled_at' => '',
        'archive_at' => '',
        'is_active' => true,
    ];

    public $featured_image;
    public $attachments = [];

    public function mount(News $news): void
    {
        $this->news = $news;

        $this->form = [
            'title' => $news->title,
            'slug' => $news->slug,
            'excerpt' => $news->excerpt ?? '',
            'content' => $news->content,
            'category_ids' => $news->categories->pluck('id')->toArray(),
            'tag_ids' => $news->tags->pluck('id')->toArray(),
            'published_at' => $news->published_at?->format('Y-m-d\TH:i'),
            'scheduled_at' => $news->scheduled_at?->format('Y-m-d\TH:i'),
            'archive_at' => $news->archive_at?->format('Y-m-d\TH:i'),
            'is_active' => $news->is_active,
        ];
    }

    public function updatedFormTitle(): void
    {
        $this->form['slug'] = str($this->form['title'])->slug()->toString();
    }

    public function update(): void
    {
        $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['nullable', 'string', 'max:255'],
            'form.excerpt' => ['nullable', 'string', 'max:500'],
            'form.content' => ['required', 'string'],
            'form.category_ids' => ['nullable', 'array'],
            'form.category_ids.*' => ['integer', 'exists:communications_categories,id'],
            'form.tag_ids' => ['nullable', 'array'],
            'form.tag_ids.*' => ['integer', 'exists:communications_tags,id'],
            'form.published_at' => ['nullable', 'date'],
            'form.scheduled_at' => ['nullable', 'date'],
            'form.archive_at' => ['nullable', 'date'],
            'form.is_active' => ['nullable', 'boolean'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $handler = app(UpdateNewsHandler::class);
        $dto = NewsDTO::fromArray($this->form);

        $handler->execute($this->news->id, $dto);

        if ($this->featured_image) {
            $this->news->addMedia($this->featured_image)->toMediaCollection('featured_image');
        }

        if ($this->attachments) {
            foreach ($this->attachments as $attachment) {
                $this->news->addMedia($attachment)->toMediaCollection('attachments');
            }
        }

        $this->dispatch('news-updated');
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = $this->news->media()->findOrFail($mediaId);
        $media->delete();
    }

    public function render()
    {
        return view('platform::livewire.news-form', [
            'categories' => Category::active()->ordered()->get(),
            'tags' => Tag::active()->ordered()->get(),
        ]);
    }
}
