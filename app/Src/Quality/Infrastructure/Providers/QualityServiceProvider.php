<?php

declare(strict_types=1);

namespace App\Src\Quality\Infrastructure\Providers;

use App\Src\Quality\Domain\Repositories\QualityRepositoryInterface;
use App\Src\Quality\Infrastructure\Persistence\EloquentQualityRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class QualityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QualityRepositoryInterface::class, EloquentQualityRepository::class);
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
            $this->loadViewsFrom($viewsPath, 'quality');
        }
    }
}
