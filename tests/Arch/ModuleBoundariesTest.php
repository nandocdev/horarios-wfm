<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Module Boundaries — ADR-0001 Flat Modular Monolith
|--------------------------------------------------------------------------
|
| Verifica mecánicamente las reglas de acceso entre módulos (§3 y §5
| de docs/tmp/Modules.md):
|
|  - Ningún módulo consume `App\Modules\{Other}\Internal\*` (hoy 0 casos,
|    pero el test previene regresiones cuando se introduzca Internal/).
|  - Cada módulo solo puede depender de Shared + contratos públicos
|    (Actions/DTOs/Events/Models/Contracts/Enums/Concerns) de otros módulos.
|
| Usa pest-plugin-arch (arch()).
*/

arch('modules do not access Internal of other modules')
    ->expect('App\Modules')
    ->not->toUse('App\Modules\*\Internal');

// Strict per-module isolation: cada módulo no puede tocar Internal de otro.
// Generado dinámicamente para los 18 módulos reales (*Module).
$modules = [
    'AnalyticsModule',
    'AuditModule',
    'CommunicationsModule',
    'ConnectModule',
    'CoreModule',
    'DirectoryModule',
    'DocumentationModule',
    'FilesystemModule',
    'GeoModule',
    'HelpdeskModule',
    'KnowledgeModule',
    'OperationsModule',
    'OrganizationModule',
    'PersonnelModule',
    'QualityModule',
    'ReportingModule',
    'WfmModule',
    'WorkflowsModule',
];

foreach ($modules as $module) {
    $others = array_diff($modules, [$module]);

    foreach ($others as $other) {
        arch("{$module} does not access {$other}\\Internal")
            ->expect("App\\Modules\\{$module}")
            ->not->toUse("App\\Modules\\{$other}\\Internal");
    }
}
