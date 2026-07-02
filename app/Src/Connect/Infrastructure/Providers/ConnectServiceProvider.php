<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Providers;

use App\Src\Connect\Domain\Ports\CiscoAprovisioningInterface;
use App\Src\Connect\Domain\Repositories\CallEventRepositoryInterface;
use App\Src\Connect\Infrastructure\Integrations\CiscoFinesseAdapter;
use App\Src\Connect\Infrastructure\Persistence\EloquentConnectRepository;
use Illuminate\Support\ServiceProvider;

final class ConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CiscoAprovisioningInterface::class, CiscoFinesseAdapter::class);
        $this->app->bind(CallEventRepositoryInterface::class, EloquentConnectRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $routesPath = __DIR__ . '/../../Presentation/Routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }
}
