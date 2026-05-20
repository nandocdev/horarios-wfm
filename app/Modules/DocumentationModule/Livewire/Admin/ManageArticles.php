<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Livewire\Admin;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\DocumentationModule\Models\Article;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ManageArticles extends Component
{
    use WithPagination;

    public $search = '';

    public $showModal = false;

    public $editingArticle = null;

    // Form fields
    public $title = '';

    public $content = '';

    public $is_published = false;

    public $selectedCategories = [];

    public $sort_order = 0;

    protected $rules = [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'is_published' => 'boolean',
        'selectedCategories' => 'array',
        'sort_order' => 'integer',
    ];

    public function render()
    {
        $articles = Article::with(['author', 'categories'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        $categories = Category::active()->ordered()->get();

        return view('documentation::livewire.admin.manage-articles', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    public function createArticle()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editArticle(Article $article)
    {
        $this->editingArticle = $article;
        $this->title = $article->title;
        $this->content = $article->content;
        $this->is_published = $article->is_published;
        $this->selectedCategories = $article->categories->pluck('id')->toArray();
        $this->sort_order = $article->sort_order;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'content' => $this->content,
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingArticle) {
            $this->editingArticle->update($data);
            $article = $this->editingArticle;
        } else {
            $data['author_id'] = Auth::id();
            $article = Article::create($data);
        }

        $article->categories()->sync($this->selectedCategories);

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Artículo guardado correctamente.',
        ]);
    }

    public function deleteArticle(Article $article)
    {
        $article->delete();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Artículo eliminado.',
        ]);
    }

    private function resetForm()
    {
        $this->editingArticle = null;
        $this->title = '';
        $this->content = '';
        $this->is_published = false;
        $this->selectedCategories = [];
        $this->sort_order = 0;
    }
}
