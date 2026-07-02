<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Providers;

use App\Src\Organization\Domain\Repositories\OrganizationRepositoryInterface;
use App\Src\Organization\Infrastructure\Persistence\EloquentOrganizationRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationRepositoryInterface::class, EloquentOrganizationRepository::class);
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
            $this->loadViewsFrom($viewsPath, 'organization');
        }
    }
}
