<?php

declare(strict_types=1);

use App\Modules\CoreModule\Providers\ModuleServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Modulos Registrados (Antigravity)
    |--------------------------------------------------------------------------
    |
    | Aquí se definen todos los Proveedores de Servicios de cada módulo del
    | Monolito Modular. Se cargan automáticamente en el AppServiceProvider.
    |
    */

    'enabled' => [
        // App/Src context providers (DDD migration)
        App\Src\Platform\Infrastructure\Providers\PlatformServiceProvider::class,
        App\Src\Identity\Infrastructure\Providers\IdentityServiceProvider::class,

        // Legacy module providers
        ModuleServiceProvider::class,
        App\Modules\PersonnelModule\Providers\ModuleServiceProvider::class,
        App\Modules\OperationsModule\Providers\ModuleServiceProvider::class,
        App\Modules\ConnectModule\Providers\ModuleServiceProvider::class,
        App\Modules\WorkflowsModule\Providers\ModuleServiceProvider::class,
        App\Modules\SupportModule\Providers\ModuleServiceProvider::class,
        App\Modules\CommunicationsModule\Providers\ModuleServiceProvider::class,
        App\Modules\AuditModule\Providers\ModuleServiceProvider::class,
        App\Modules\WfmModule\Providers\ModuleServiceProvider::class,
        App\Modules\HelpdeskModule\Providers\ModuleServiceProvider::class,
        App\Modules\DocumentationModule\Providers\ModuleServiceProvider::class,
        App\Modules\FilesystemModule\Providers\ModuleServiceProvider::class,
        App\Modules\KnowledgeModule\Providers\ModuleServiceProvider::class,
    ],
];
