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
        App\Src\Organization\Infrastructure\Providers\OrganizationServiceProvider::class,
        App\Src\HumanResources\Infrastructure\Providers\HumanResourcesServiceProvider::class,
        App\Src\Connect\Infrastructure\Providers\ConnectServiceProvider::class,
        App\Src\Wfm\Infrastructure\Providers\WfmServiceProvider::class,
        App\Src\TimeAndAttendance\Infrastructure\Providers\TimeAndAttendanceServiceProvider::class,
        App\Src\Workflows\Infrastructure\Providers\WorkflowsServiceProvider::class,
        App\Src\Knowledge\Infrastructure\Providers\KnowledgeServiceProvider::class,
        App\Src\Quality\Infrastructure\Providers\QualityServiceProvider::class,

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
