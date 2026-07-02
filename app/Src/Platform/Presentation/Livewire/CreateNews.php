<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\Tag;
use App\Src\Platform\Application\DTOs\NewsDTO;
use App\Src\Platform\Application\Handlers\CreateNewsHandler;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Crear Noticia')]
class CreateNews extends Component
{
    use WithFileUploads;

    public string $mode = 'create';

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
        'workflow_action' => 'save_draft',
    ];

    public $featured_image;
    public $attachments = [];

    public function updatedFormTitle(): void
    {
        $this->form['slug'] = str($this->form['title'])->slug()->toString();
    }

    public function save(): void
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

        $handler = app(CreateNewsHandler::class);
        $dto = NewsDTO::fromArray($this->form);

        $news = $handler->execute($dto, auth()->id());

        if ($this->featured_image) {
            $news->addMedia($this->featured_image)->toMediaCollection('featured_image');
        }

        if ($this->attachments) {
            foreach ($this->attachments as $attachment) {
                $news->addMedia($attachment)->toMediaCollection('attachments');
            }
        }

        $this->dispatch('news-created');
    }

    public function render()
    {
        return view('platform::livewire.news-form', [
            'categories' => Category::active()->ordered()->get(),
            'tags' => Tag::active()->ordered()->get(),
        ]);
    }
}
