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
        ModuleServiceProvider::class,
        App\Modules\PersonnelModule\Providers\ModuleServiceProvider::class,
        App\Modules\OperationsModule\Providers\ModuleServiceProvider::class,
        App\Modules\ConnectModule\Providers\ModuleServiceProvider::class,
        App\Modules\CommunicationsModule\Providers\ModuleServiceProvider::class,
        App\Modules\AuditModule\Providers\ModuleServiceProvider::class,
        App\Modules\WfmModule\Providers\ModuleServiceProvider::class,
        App\Modules\HelpdeskModule\Providers\ModuleServiceProvider::class,
        App\Modules\DocumentationModule\Providers\ModuleServiceProvider::class,
        App\Modules\FilesystemModule\Providers\ModuleServiceProvider::class,
        App\Modules\KnowledgeModule\Providers\ModuleServiceProvider::class,
    ],
];
