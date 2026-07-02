<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Providers;

use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use App\Src\Identity\Infrastructure\Persistence\EloquentUserRepository;
use App\Src\Identity\Infrastructure\Services\BcryptPasswordHasher;
use App\Src\Identity\Presentation\Livewire\ManageUsers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PasswordHasherInterface::class, BcryptPasswordHasher::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerLivewire();
        $this->loadViews();
        $this->registerPolicies();
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../Presentation/Routes/web.php');
    }

    private function registerLivewire(): void
    {
        Livewire::component('identity.manage-users', ManageUsers::class);
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../Presentation/Resources/views', 'identity');
    }

    private function registerPolicies(): void
    {
        Gate::define('admin.system', function (EloquentUser $user) {
            return $user->hasAnyRole(['admin', 'wfm']);
        });
    }
}
