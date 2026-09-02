<?php

declare(strict_types=1);

// Compatibilidad: ruta histórica app/Helpers/toast.php
// El helper canónico vive en app/Shared/Helpers/toast.php (composer.json)
// Este archivo solo re-exporta para deployments con vendor cacheado
// que aún referencia la ruta antigua. Se puede eliminar tras
// composer dump-autoload en producción.

if (file_exists(__DIR__.'/../Shared/Helpers/toast.php')) {
    require_once __DIR__.'/../Shared/Helpers/toast.php';
}
