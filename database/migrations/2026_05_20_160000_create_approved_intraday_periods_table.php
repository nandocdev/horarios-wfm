<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de periodos intradía aprobados por WFM.
 * Permite que WFM autorice bloques de tiempo por equipo para actividades intradía
 * que los coordinadores pueden asignar a sus operadores.
 *
 * [RIESGOS]
 * - Concurrencia en incremento de slots usados → debe controlarse con lockForUpdate en la acción.
 * - Dates sin timezone awareness → usar timestampsTz para uniformidad con el resto del esquema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approved_intraday_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('activity_definition_id')->constrained('scheduled_activity_definitions')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('max_slots')->default(1);
            $table->string('notes', 500)->nullable();
            $table->timestampsTz();

            // Índices para consultas frecuentes por equipo y fecha
            $table->index(['team_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approved_intraday_periods');
    }
};
