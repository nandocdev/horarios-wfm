<?php

declare(strict_types=1);

use App\Modules\CoreModule\Providers\ModuleServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Módulos Registrados
    |--------------------------------------------------------------------------
    |
    | Proveedores de Servicios de cada módulo del Monolito Modular.
    | Cargados automáticamente en AppServiceProvider en el orden definido.
    | El orden respeta dependencias: módulos base primero, luego los que
    | dependen de ellos.
    |
    */

    'enabled' => [

        // ─── Módulos Base (infraestructura, sin dependencias de otros módulos) ───
        ModuleServiceProvider::class,                     // CoreModule: Auth (Fortify), RBAC (Spatie), usuarios, roles, sistema

        // ─── Módulos de Organización (dependen de CoreModule) ───
        App\Modules\OrganizationModule\Providers\ModuleServiceProvider::class,  // OrganizationModule: Direcciones, departamentos, cargos
        App\Modules\GeoModule\Providers\ModuleServiceProvider::class,           // GeoModule: Provincias, distritos, corregimientos
        App\Modules\PersonnelModule\Providers\ModuleServiceProvider::class,     // PersonnelModule: Empleados, equipos, asignaciones

        // ─── Módulos de Operaciones (dependen de PersonnelModule) ───
        App\Modules\OperationsModule\Providers\ModuleServiceProvider::class,     // OperationsModule: KPIs, dashboards, productividad, adherencia
        App\Modules\ConnectModule\Providers\ModuleServiceProvider::class,        // ConnectModule: Integración Cisco UCCX/CUIC/Finesse, llamadas, colas

        // ─── Módulos de Comunicación y Auditoría ───
        App\Modules\CommunicationsModule\Providers\ModuleServiceProvider::class, // CommunicationsModule: Noticias, encuestas, shoutouts, comentarios
        App\Modules\AuditModule\Providers\ModuleServiceProvider::class,         // AuditModule: Logging de auditoría, exportación

        // ─── Módulo WFM (núcleo del negocio, depende de PersonnelModule + ConnectModule) ───
        App\Modules\WfmModule\Providers\ModuleServiceProvider::class,           // WfmModule: Turnos, planificación semanal, swaps, permisos, intradía

        // ─── Módulo de Reportería WFM ───
        App\Modules\ReportingModule\Providers\ModuleServiceProvider::class,     // ReportingModule: Reportes descargables PDF/XLS

        // ─── Módulos de Soporte ───
        App\Modules\HelpdeskModule\Providers\ModuleServiceProvider::class,      // HelpdeskModule: Tickets de soporte, SLA, bandeja
        App\Modules\DocumentationModule\Providers\ModuleServiceProvider::class, // DocumentationModule: Wiki/documentación interna
        App\Modules\FilesystemModule\Providers\ModuleServiceProvider::class,    // FilesystemModule: Archivos, carpetas, descargas, cuotas
        App\Modules\KnowledgeModule\Providers\ModuleServiceProvider::class,     // KnowledgeModule: Base de conocimiento operativo
        App\Modules\QualityModule\Providers\ModuleServiceProvider::class,       // QualityModule: Evaluación de calidad de llamadas

        // ─── Módulo de Aprobaciones (Workflows) ───
        App\Modules\WorkflowsModule\Providers\ModuleServiceProvider::class,     // WorkflowsModule: Motor de aprobaciones multinivel
    ],
];
