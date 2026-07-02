<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Providers;

use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;
use App\Src\TimeAndAttendance\Infrastructure\Console\ReconcileAttendanceCommand;
use App\Src\TimeAndAttendance\Infrastructure\Integrations\IdentityValidatorInterface;
use App\Src\TimeAndAttendance\Infrastructure\Integrations\NullIdentityValidator;
use App\Src\TimeAndAttendance\Infrastructure\Persistence\EloquentAttendanceRepository;
use App\Src\TimeAndAttendance\Presentation\Livewire\ListIncidents;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class TimeAndAttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AttendanceRepositoryInterface::class, EloquentAttendanceRepository::class);
        $this->app->bind(IdentityValidatorInterface::class, NullIdentityValidator::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerLivewire();
        $this->loadViews();
        $this->registerCommands();
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../Presentation/Routes/web.php');
    }

    private function registerLivewire(): void
    {
        Livewire::component('ta.list-incidents', ListIncidents::class);
    }

    private function loadViews(): void
    {
        $viewsPath = __DIR__ . '/../../Presentation/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'ta');
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ReconcileAttendanceCommand::class]);
        }
    }
}
