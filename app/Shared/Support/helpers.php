<?php

declare(strict_types=1);

use Illuminate\Support\Str;

if (! function_exists('module_path')) {
    /**
     * Retorna el path absoluto a un módulo del monolito.
     *
     * Ej: module_path('WfmModule', 'Actions/PublishWeeklySchedule.php')
     */
    function module_path(string $module, string $path = ''): string
    {
        $base = app_path("Modules/{$module}".($path !== '' ? "/{$path}" : ''));

        return $base;
    }
}

if (! function_exists('shared_path')) {
    /**
     * Retorna el path absoluto a Shared/.
     */
    function shared_path(string $path = ''): string
    {
        return app_path('Shared'.($path !== '' ? "/{$path}" : ''));
    }
}

if (! function_exists('is_production')) {
    /**
     * Helper semántico para checks de entorno.
     */
    function is_production(): bool
    {
        return app()->isProduction();
    }
}

if (! function_exists('ulid')) {
    /**
     * Genera un ULID (string) — wrapper sobre Str::ulid().
     */
    function ulid(): string
    {
        return (string) Str::ulid();
    }
}
