<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

/**
 * MIGRACIÓN OBSOLETA — Consolidada en 2026_07_02_000001_unified_schedule_module_schema
 *
 * Esta migración se mantiene en el historio por compatibilidad, pero no hace nada.
 * Toda la lógica ha sido centralizada en la migración unificada para evitar fragmentación.
 */
return new class extends Migration {
    public function up(): void {
        // NOOP: Tablas ya creadas por migración unificada
    }

    public function down(): void {
        // NOOP: No se elimina nada aquí
    }
};
