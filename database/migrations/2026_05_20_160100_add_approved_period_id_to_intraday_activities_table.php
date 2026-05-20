<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la relación opcional con el periodo aprobado a las asignaciones de actividades intradía.
 * Permite rastrear si una asignación fue creada dentro de un periodo autorizado por WFM.
 *
 * [RIESGOS]
 * - Nullable por retro-compatibilidad con asignaciones directas previas (sin periodo).
 * - nullOnDelete asegura que si se elimina el periodo, la actividad se mantiene pero pierde la referencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intraday_activities', function (Blueprint $table) {
            $table->foreignId('approved_period_id')
                ->nullable()
                ->after('activity_type_id')
                ->constrained('approved_intraday_periods')
                ->nullOnDelete();

            $table->index('approved_period_id');
        });
    }

    public function down(): void
    {
        Schema::table('intraday_activities', function (Blueprint $table) {
            $table->dropForeign(['approved_period_id']);
            $table->dropIndex(['approved_period_id']);
            $table->dropColumn('approved_period_id');
        });
    }
};
