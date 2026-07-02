<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Providers;

use App\Src\Identity\Domain\Repositories\AppSettingRepositoryInterface;
use App\Src\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Src\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Src\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Src\Identity\Domain\Services\PasswordHasherInterface;
use App\Src\Identity\Infrastructure\Listeners\UpdateLastLoginAtListener;
use App\Src\Identity\Infrastructure\Persistence\EloquentAppSettingRepository;
use App\Src\Identity\Infrastructure\Persistence\EloquentPermissionRepository;
use App\Src\Identity\Infrastructure\Persistence\EloquentRoleRepository;
use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use App\Src\Identity\Infrastructure\Persistence\EloquentUserRepository;
use App\Src\Identity\Infrastructure\Observers\RoleObserver;
use App\Src\Identity\Infrastructure\Services\BcryptPasswordHasher;
use App\Src\Identity\Infrastructure\Persistence\EloquentRole;
use App\Src\Identity\Presentation\Livewire\CreateUserForm;
use App\Src\Identity\Presentation\Livewire\EditUserForm;
use App\Src\Identity\Presentation\Livewire\ListRoles;
use App\Src\Identity\Presentation\Livewire\ListUsers;
use App\Src\Identity\Presentation\Livewire\ManageUsers;
use App\Src\Identity\Presentation\Livewire\SettingsAppearance;
use App\Src\Identity\Presentation\Livewire\SettingsProfile;
use App\Src\Identity\Presentation\Livewire\SettingsSecurity;
use App\Src\Identity\Presentation\Policies\RolePolicy;
use App\Src\Identity\Presentation\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, EloquentPermissionRepository::class);
        $this->app->bind(AppSettingRepositoryInterface::class, EloquentAppSettingRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerLivewire();
        $this->loadViews();
        $this->registerPolicies();
        $this->registerObservers();
        $this->registerListeners();
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../Presentation/Routes/web.php');
    }

    private function registerLivewire(): void
    {
        Livewire::component('identity.manage-users', ManageUsers::class);
        Livewire::component('identity.list-users', ListUsers::class);
        Livewire::component('identity.create-user-form', CreateUserForm::class);
        Livewire::component('identity.edit-user-form', EditUserForm::class);
        Livewire::component('identity.list-roles', ListRoles::class);
        Livewire::component('identity.settings-profile', SettingsProfile::class);
        Livewire::component('identity.settings-security', SettingsSecurity::class);
        Livewire::component('identity.settings-appearance', SettingsAppearance::class);
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

        Gate::policy(EloquentUser::class, UserPolicy::class);
        Gate::policy(EloquentRole::class, RolePolicy::class);
    }

    private function registerObservers(): void
    {
        EloquentRole::observe(RoleObserver::class);
    }

    private function registerListeners(): void
    {
        Event::listen(Login::class, UpdateLastLoginAtListener::class);
    }
}
