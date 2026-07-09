<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Providers;

use App\Modules\DocumentationModule\Livewire\Admin\ManageWikiArticles;
use App\Modules\DocumentationModule\Livewire\Public\WikiArticleDetail;
use App\Modules\DocumentationModule\Livewire\Public\WikiArticleIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load routes
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        // Load views
        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'documentation');

            // Register Livewire components
            Livewire::component('documentation.public.article-index', WikiArticleIndex::class);
            Livewire::component('documentation.public.article-detail', WikiArticleDetail::class);
            Livewire::component('documentation.admin.manage-articles', ManageWikiArticles::class);
        }
    }
}
