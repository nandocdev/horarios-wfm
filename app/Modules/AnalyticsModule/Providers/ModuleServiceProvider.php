<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Providers;

use App\Shared\Providers\AbstractModuleServiceProvider;

class ModuleServiceProvider extends AbstractModuleServiceProvider
{
    protected ?string $viewNamespace = 'analytics';
}
