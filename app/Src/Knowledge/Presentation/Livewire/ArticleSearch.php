<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Presentation\Livewire;

use App\Src\Knowledge\Infrastructure\Persistence\EloquentArticle;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Base de Conocimiento')]
class ArticleSearch extends Component
{
    public string $search = '';
    public ?int $categoryId = null;

    public function updatingSearch(): void
    {
        $this->skipRender();
    }

    public function render()
    {
        $articles = EloquentArticle::with('tags', 'queues')
            ->where('status', 'published')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $search = "%{$this->search}%";
                $sub->where('title', 'ilike', $search)
                    ->orWhere('summary', 'ilike', $search)
                    ->orWhere('content', 'ilike', $search);
            }))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->latest('published_at')
            ->get();

        $categories = \App\Src\Knowledge\Infrastructure\Persistence\EloquentCategory::orderBy('name')->get();

        return view('knowledge::livewire.article-search', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }
}
