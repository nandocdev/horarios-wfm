<?php

declare(strict_types=1);

namespace App\Modules\DocumentationModule\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (file_exists(__DIR__ . '/../Routes/web.php')) {
            \Illuminate\Support\Facades\Route::middleware('web')->group(__DIR__ . '/../Routes/web.php');
        }

        // Load views
        if (is_dir(__DIR__ . '/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'documentation');
            
            // Register Livewire components
            \Livewire\Livewire::component('documentation.public.article-index', \App\Modules\DocumentationModule\Livewire\Public\ArticleIndex::class);
            \Livewire\Livewire::component('documentation.public.article-detail', \App\Modules\DocumentationModule\Livewire\Public\ArticleDetail::class);
            \Livewire\Livewire::component('documentation.admin.manage-articles', \App\Modules\DocumentationModule\Livewire\Admin\ManageArticles::class);
        }
    }
}
