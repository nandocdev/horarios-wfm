<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Providers;

use App\Src\Wfm\Domain\Repositories\ScheduleRepositoryInterface;
use App\Src\Wfm\Infrastructure\Persistence\EloquentScheduleRepository;
use App\Src\Wfm\Presentation\Livewire\WeeklyPlanning;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class WfmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScheduleRepositoryInterface::class, EloquentScheduleRepository::class);
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
        Livewire::component('wfm.weekly-planning', WeeklyPlanning::class);
    }

    private function loadViews(): void
    {
        $viewsPath = __DIR__ . '/../../Presentation/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'wfm');
        }
    }
}
