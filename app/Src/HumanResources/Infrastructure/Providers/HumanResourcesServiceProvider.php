<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Infrastructure\Providers;

use App\Src\HumanResources\Domain\Repositories\EmployeeRecordRepositoryInterface;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeRecordRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class HumanResourcesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeRecordRepositoryInterface::class, EloquentEmployeeRecordRepository::class);
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
            $this->loadViewsFrom($viewsPath, 'hr');
        }
    }
}
