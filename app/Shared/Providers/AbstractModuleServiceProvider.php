<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor base para módulos del monolito (ADR-0001 §4).
 *
 * Homogeneiza el patrón de los 18 ModuleServiceProvider:
 *  - register(): solo bindings de contenedor (sin lógica de dominio)
 *  - boot(): carga declarativa de migraciones, rutas y vistas
 *
 * Los módulos con necesidades especiales (policies, listeners, Livewire)
 * pueden sobrescribir hook() manteniendo la convención.
 */
abstract class AbstractModuleServiceProvider extends ServiceProvider
{
    /**
     * Nombre del view namespace (ej: 'wfm', 'communications').
     * Si es null no se cargan vistas.
     */
    protected ?string $viewNamespace = null;

    /**
     * Path relativo a vistas dentro del módulo.
     */
    protected string $viewPath = '/../Resources/Views';

    /**
     * Path relativo a migraciones.
     */
    protected string $migrationPath = '/../Database/Migrations';

    /**
     * Path relativo a rutas web.
     */
    protected string $routePath = '/../Routes/web.php';

    public function register(): void
    {
        // Override en módulos que necesiten bindings.
    }

    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleRoutes();
        $this->loadModuleViews();
    }

    protected function loadModuleMigrations(): void
    {
        $candidate = $this->callerDir().$this->migrationPath;

        if (is_dir($candidate)) {
            $this->loadMigrationsFrom($candidate);
        }
    }

    protected function loadModuleRoutes(): void
    {
        $candidate = $this->callerDir().$this->routePath;

        if (is_file($candidate)) {
            $this->loadRoutesFrom($candidate);
        }
    }

    protected function loadModuleViews(): void
    {
        if ($this->viewNamespace === null) {
            return;
        }

        $candidate = $this->callerDir().$this->viewPath;

        if (is_dir($candidate)) {
            $this->loadViewsFrom($candidate, $this->viewNamespace);
        }
    }

    /**
     * Hook para registrar policies, observers, listeners, etc.
     * Mantener liviano; mover lógica pesada a dedicated ServiceProviders
     * si el módulo crece.
     */
    protected function registerPolicies(): void {}

    private function callerDir(): string
    {
        $ref = new \ReflectionClass($this);

        return dirname($ref->getFileName());
    }
}
