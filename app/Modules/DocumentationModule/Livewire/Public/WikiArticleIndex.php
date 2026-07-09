<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Livewire\Public;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\DocumentationModule\Models\WikiArticle;
use Livewire\Component;
use Livewire\WithPagination;

class WikiArticleIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $category_id = null;

    public function render()
    {
        $categories = Category::active()
            ->whereHas('wikiArticles', function ($query) {
                $query->published();
            })
            ->ordered()
            ->get();

        $articles = WikiArticle::published()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('content', 'like', '%'.$this->search.'%');
            })
            ->when($this->category_id, function ($query) {
                $query->whereHas('categories', function ($q) {
                    $q->where('categories.id', $this->category_id);
                });
            })
            ->ordered()
            ->paginate(12);

        return view('documentation::livewire.public.wiki-article-index', [
            'articles' => $articles,
            'categories' => $categories,
        ])->layout('layouts.app');
    }

    public function filterByCategory($id)
    {
        $this->category_id = $id;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'category_id']);
        $this->resetPage();
    }
}
