<?php

declare(strict_types=1);

namespace App\Modules\GeoModule\Providers;

use App\Shared\Providers\AbstractModuleServiceProvider;

class ModuleServiceProvider extends AbstractModuleServiceProvider
{
    protected ?string $viewNamespace = 'geo';

    public function boot(): void
    {
        parent::boot();
        // Geo expone doble namespace legacy 'location' para compatibilidad
        $callerDir = dirname((new \ReflectionClass($this))->getFileName());
        $viewPath = $callerDir.'/../Resources/Views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'location');
        }
    }
}
