<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Livewire\Public;

use App\Modules\DocumentationModule\Models\Article;
use Livewire\Component;

class ArticleDetail extends Component
{
    public Article $article;

    public function mount(string $slug)
    {
        $this->article = Article::published()->where('slug', $slug)->firstOrFail();
        
        // Incrementar contador de vistas de forma simple
        $this->article->increment('view_count');
    }

    public function render()
    {
        return view('documentation::livewire.public.article-detail')
            ->layout('layouts.app');
    }
}
