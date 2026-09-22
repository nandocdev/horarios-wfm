<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor base para módulos src/ (architecture.md §R4).
 * Cada módulo implementa module(), alias(), routeFiles().
 */
abstract class AbstractModuleProvider extends ServiceProvider
{
    abstract public function module(): string;

    abstract public function alias(): string;

    /**
     * @return array<string, string> map alias => path relativo o absoluto
     */
    abstract public function routeFiles(): array;

    protected string $viewPath = '/../../Presentation/Views';

    protected string $migrationPath = '/../Database/Migrations';

    public function register(): void {}

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
        foreach ($this->routeFiles() as $file) {
            $candidate = $file;

            if (! str_starts_with($candidate, '/')) {
                $candidate = $this->callerDir().'/'.$file;
            }

            if (is_file($candidate)) {
                $this->loadRoutesFrom($candidate);
            }
        }
    }

    protected function loadModuleViews(): void
    {
        $candidate = $this->callerDir().$this->viewPath;

        if (is_dir($candidate)) {
            $this->loadViewsFrom($candidate, $this->alias());
        }
    }

    protected function callerDir(): string
    {
        $ref = new \ReflectionClass($this);

        return dirname($ref->getFileName());
    }
}
