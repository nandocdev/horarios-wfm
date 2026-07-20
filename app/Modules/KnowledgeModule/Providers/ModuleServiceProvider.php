<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Providers;

use App\Modules\KnowledgeModule\Livewire\KnowledgeArticleDetail;
use App\Modules\KnowledgeModule\Livewire\ManageKnowledgeArticles;
use App\Modules\KnowledgeModule\Livewire\OperatorView;
use App\Modules\KnowledgeModule\Livewire\UpsertKnowledgeArticle;
use App\Modules\KnowledgeModule\Models\ArticleVersion;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use App\Modules\KnowledgeModule\Policies\ArticlePolicy;
use App\Modules\KnowledgeModule\Policies\ArticleVersionPolicy;
use App\Modules\KnowledgeModule\Policies\KnowledgeCategoryPolicy;
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
        Gate::policy(KnowledgeArticle::class, ArticlePolicy::class);
        Gate::policy(KnowledgeCategory::class, KnowledgeCategoryPolicy::class);
        Gate::policy(ArticleVersion::class, ArticleVersionPolicy::class);

        // Cargar rutas
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        // Cargar vistas y registrar componentes Livewire
        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'knowledge');

            // Registrar componentes en Livewire
            Livewire::component('knowledge.operator-view', OperatorView::class);
            Livewire::component('knowledge.manage-articles', ManageKnowledgeArticles::class);
            Livewire::component('knowledge.article-detail', KnowledgeArticleDetail::class);
            Livewire::component('knowledge.upsert-article', UpsertKnowledgeArticle::class);
        }
    }
}
/**
 * [RIESGOS]
 * - Carga Duplicada de Rutas → Envuelto bajo el middleware 'web' para heredar la sesión, token CSRF y validación de usuarios autenticados.
 */
