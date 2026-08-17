<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Providers;

use App\Modules\DirectoryModule\Livewire\ManageDirectoryUnits;
use App\Modules\DirectoryModule\Livewire\UpsertDirectoryUnit;
use App\Modules\DirectoryModule\Models\Unit;
use App\Modules\DirectoryModule\Policies\UnitPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Service Provider que registra rutas, vistas, políticas y componentes
 * Livewire del directorio de unidades operativas.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Unit::class, UnitPolicy::class);

        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'directory');

            Livewire::component('directory.manage-units', ManageDirectoryUnits::class);
            Livewire::component('directory.upsert-unit', UpsertDirectoryUnit::class);
        }
    }
}
