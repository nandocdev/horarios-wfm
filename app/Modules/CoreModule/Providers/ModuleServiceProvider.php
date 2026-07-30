<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Providers;

use App\Modules\CoreModule\Actions\Fortify\CreateNewUser;
use App\Modules\CoreModule\Actions\Fortify\ResetUserPassword;
use App\Modules\CoreModule\Console\Commands\SeedNotificationConfigs;
use App\Modules\CoreModule\Listeners\UpdateLastLoginAtListener;
use App\Modules\CoreModule\Livewire\Admin\NotificationAdmin;
use App\Modules\CoreModule\Livewire\Roles\ListRoles;
use App\Modules\CoreModule\Livewire\Shared\NotificationBell;
use App\Modules\CoreModule\Livewire\Shared\NotificationHistory;
use App\Modules\CoreModule\Livewire\Toast;
use App\Modules\CoreModule\Livewire\Users\CreateUser;
use App\Modules\CoreModule\Livewire\Users\EditUser;
use App\Modules\CoreModule\Livewire\Users\ListUsers;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\CoreModule\Observers\RoleObserver;
use App\Modules\CoreModule\Policies\RolePolicy;
use App\Modules\CoreModule\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * El Proveedor de Servicios del Módulo Core (Identidad y Acceso).
     * Centraliza la lógica de autenticación (Fortify), roles y permisos.
     */
    public function register(): void
    {
        // Registro de bindings del módulo
    }

    public function boot(): void
    {
        // 0. Migraciones del módulo
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // 1. Carga de infraestructura modular
        $this->registerInfrastructure();

        // 2. Configuración de Autenticación (Fortify)
        $this->configureFortify();

        // 3. Configuración de Rate Limiting
        $this->configureRateLimiting();

        // 4. Registro de listeners de autenticación
        $this->registerEventListeners();

        // 5. Observers de entidad
        $this->registerObservers();

        // 6. Autorización (RBAC)
        $this->registerPolicies();

        // 7. Comandos
        $this->registerCommands();
    }

    /**     * Registra observadores del módulo.
     */
    protected function registerObservers(): void
    {
        Role::observe(RoleObserver::class);
    }

    /**     * Registra las políticas de autorización del módulo.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // Permiso maestro para configuración del sistema (Mantenimiento)
        Gate::define('admin.system', function (User $user) {
            return $user->hasAnyRole(['admin', 'wfm']);
        });
    }

    /**
     * Registra rutas, vistas y namespaces del módulo.
     */
    protected function registerInfrastructure(): void
    {
        $viewsPath = __DIR__.'/../Resources/Views';

        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'core');
            Blade::anonymousComponentPath($viewsPath, 'core');

            // Registro manual de componentes para control granular
            Livewire::component('core.users.list-users', ListUsers::class);
            Livewire::component('core.users.create-user', CreateUser::class);
            Livewire::component('core.users.edit-user', EditUser::class);
            Livewire::component('core.roles.list-roles', ListRoles::class);
            Livewire::component('core.toast', Toast::class);
            Livewire::component('core.shared.notification-bell', NotificationBell::class);
            Livewire::component('core.shared.notification-history', NotificationHistory::class);
            Livewire::component('core.admin.notification-admin', NotificationAdmin::class);
        }
    }

    /**
     * Configura las acciones y vistas de Laravel Fortify para el CoreModule.
     */
    protected function configureFortify(): void
    {
        // Configuración de Acciones
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // Configuración de Vistas (Modularizadas bajo 'core::')
        Fortify::loginView(fn () => view('core::auth.login'));
        Fortify::verifyEmailView(fn () => view('core::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('core::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('core::auth.confirm-password'));
        Fortify::registerView(fn () => view('core::auth.register'));
        Fortify::resetPasswordView(fn () => view('core::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('core::auth.forgot-password'));
    }

    /**
     * Configura el limitador de velocidad para login y 2FA.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Registra listeners de eventos del módulo.
     */
    protected function registerEventListeners(): void
    {
        Event::listen(Login::class, UpdateLastLoginAtListener::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedNotificationConfigs::class,
            ]);
        }
    }
}
