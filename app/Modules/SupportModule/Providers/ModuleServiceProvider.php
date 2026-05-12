<?php

declare(strict_types=1);

namespace App\Modules\SupportModule\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registro de bindings del módulo
    }

    public function boot(): void
    {
        // Registro de rutas, vistas, etc.
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        if (is_dir(__DIR__.'/../Resources/Views')) {
            $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'support');
        }
    }
}
