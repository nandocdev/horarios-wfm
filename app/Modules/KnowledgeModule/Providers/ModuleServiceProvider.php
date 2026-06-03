<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Providers;

use App\Modules\KnowledgeModule\Livewire\ArticleDetail;
use App\Modules\KnowledgeModule\Livewire\ManageArticles;
use App\Modules\KnowledgeModule\Livewire\OperatorView;
use App\Modules\KnowledgeModule\Models\Article;
use App\Modules\KnowledgeModule\Policies\ArticlePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Service Provider encargado de registrar componentes, rutas, vistas y políticas de seguridad del módulo.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar políticas de seguridad
        Gate::policy(Article::class, ArticlePolicy::class);

        // Cargar rutas
        if (file_exists(__DIR__ . '/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__ . '/../Routes/web.php');
        }

        // Cargar vistas y registrar componentes Livewire
        if (is_dir(__DIR__ . '/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'knowledge');

            // Registrar componentes en Livewire
            Livewire::component('knowledge.operator-view', OperatorView::class);
            Livewire::component('knowledge.manage-articles', ManageArticles::class);
            Livewire::component('knowledge.article-detail', ArticleDetail::class);
            Livewire::component('knowledge.upsert-article', \App\Modules\KnowledgeModule\Livewire\UpsertArticle::class);
        }
    }
}
/**
 * [RIESGOS]
 * - Carga Duplicada de Rutas → Envuelto bajo el middleware 'web' para heredar la sesión, token CSRF y validación de usuarios autenticados.
 */
