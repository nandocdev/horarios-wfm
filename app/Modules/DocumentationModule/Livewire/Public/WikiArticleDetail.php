<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Livewire\Public;

use App\Modules\DocumentationModule\Models\WikiArticle;
use Livewire\Component;

class WikiArticleDetail extends Component
{
    public WikiArticle $article;

    public function mount(string $slug)
    {
        $this->article = WikiArticle::published()->where('slug', $slug)->firstOrFail();

        // Incrementar contador de vistas de forma simple
        $this->article->increment('view_count');
    }

    public function render()
    {
        return view('documentation::livewire.public.wiki-article-detail')
            ->layout('layouts.app');
    }
}
