<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Infrastructure\Providers;

use App\Src\Knowledge\Domain\Repositories\KnowledgeRepositoryInterface;
use App\Src\Knowledge\Infrastructure\Persistence\EloquentKnowledgeRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class KnowledgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KnowledgeRepositoryInterface::class, EloquentKnowledgeRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadViews();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../Presentation/Routes/web.php');
    }

    private function loadViews(): void
    {
        $viewsPath = __DIR__ . '/../../Presentation/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'knowledge');
        }
    }
}
