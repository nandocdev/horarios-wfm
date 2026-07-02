<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Providers;

use App\Src\Connect\Domain\Ports\CiscoAprovisioningInterface;
use App\Src\Connect\Infrastructure\Integrations\CiscoFinesseAdapter;
use Illuminate\Support\ServiceProvider;

final class ConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CiscoAprovisioningInterface::class, CiscoFinesseAdapter::class);
    }
}
