<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Infrastructure\Persistence\Repositories\EloquentLocationCatalogRepository;
use Src\Shared\Infrastructure\Providers\AbstractModuleProvider;

class LocationModuleProvider extends AbstractModuleProvider
{
    public function module(): string
    {
        return 'location';
    }

    public function alias(): string
    {
        return 'location';
    }

    /**
     * @return array<string, string>
     */
    public function routeFiles(): array
    {
        return [
            'web' => __DIR__.'/../../Presentation/Routes/web.php',
        ];
    }

    public function register(): void
    {
        $this->app->singleton(LocationCatalogInterface::class, EloquentLocationCatalogRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Route::middleware(['web', 'auth'])->group(function (): void {
            // Routes are loaded via AbstractModuleProvider, but ensure middleware is applied
        });
    }
}
