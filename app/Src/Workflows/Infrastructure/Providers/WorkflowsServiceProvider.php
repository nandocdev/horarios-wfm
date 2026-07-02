<?php

declare(strict_types=1);

namespace App\Src\Workflows\Infrastructure\Providers;

use App\Src\Workflows\Domain\Repositories\WorkflowRepositoryInterface;
use App\Src\Workflows\Infrastructure\Persistence\EloquentWorkflowRepository;
use App\Src\Workflows\Presentation\Livewire\ApprovalInbox;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class WorkflowsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkflowRepositoryInterface::class, EloquentWorkflowRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerLivewire();
        $this->loadViews();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../Presentation/Routes/web.php');
    }

    private function registerLivewire(): void
    {
        Livewire::component('workflows.approval-inbox', ApprovalInbox::class);
    }

    private function loadViews(): void
    {
        $viewsPath = __DIR__ . '/../../Presentation/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'workflows');
        }
    }
}
