<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Unificada del Módulo Schedule
 *
 * Consolida todas las definiciones de tablas, vistas, índices y tipos
 * en un único archivo para garantizar consistencia y mantenibilidad.
 *
 * Tablas incluidas:
 *   - Catálogos: schedules, absence_reason_codes, agent_states, activity_types, scheduled_activity_definitions
 *   - Planificación: weekly_schedules, weekly_schedule_assignments, weekly_team_assignments
 *   - Intraday: intraday_activities
 *   - Excepciones: schedule_exceptions
 *   - Realtime: agent_realtime_states (PostgreSQL UNLOGGED)
 *
 * Tablas separadas (migración propia):
 *   - approved_intraday_periods (2026_05_20_160000) — mantiene estructura de equipo
 */
return new class extends Migration {
    public function up(): void {
        // ============================================
        // 1. CONFIGURACIÓN BASE DE POSTGRESQL
        // ============================================
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        }

        // ============================================
        // 2. CATÁLOGOS DE AUSENCIAS Y ESTADOS
        // ============================================

        // Códigos de ausencia / justificación
        Schema::create('absence_reason_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('short_code', 10)->unique();
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_excused')->default(true);
            $table->string('color', 20)->nullable();
            $table->timestampsTz();
        });

        // Estados de agente (mapeo API externa: Cisco, Avaya, etc.)
        Schema::create('agent_states', function (Blueprint $table) {
            $table->id();
            $table->string('external_code', 50)->unique(); // Código que devuelve API
            $table->string('display_name', 100);
            $table->boolean('is_productive')->default(false);
            $table->string('color_hex', 7)->default('#cbd5e1');
            $table->timestampsTz();
        });

        // ============================================
        // 3. DEFINICIONES DE TURNOS Y ACTIVIDADES
        // ============================================

        // Tabla de turnos base (shift definitions)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('total_minutes');

            $table->integer('break_minutes')->default(15);
            $table->integer('lunch_minutes')->default(45);
            $table->boolean('is_lunch_paid')->default(true);
            $table->boolean('is_break_paid')->default(true);

            $table->jsonb('allowed_days')->default('[1, 2, 3, 4, 5]');

            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['is_active']);
        });

        // Tipos de actividades intraday
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('color', 20)->nullable();
            $table->boolean('is_productive')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->timestampsTz();
        });

        // Definiciones de actividades programadas (templates)
        Schema::create('scheduled_activity_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('activity_type_id')->constrained('activity_types')->cascadeOnDelete();
            $table->integer('default_duration_minutes')->nullable();
            $table->string('default_location')->nullable();
            $table->string('default_instructor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['is_active']);
        });

        // ============================================
        // 4. PLANIFICACIÓN SEMANAL (THE GRID)
        // ============================================

        // Contenedor de planificación semanal
        Schema::create('weekly_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('week_start_date')->unique(); // Lunes
            $table->date('week_end_date');             // Domingo
            $table->string('status', 20)->default('draft'); // draft, published, closed
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();

            $table->index(['status']);
            $table->index(['week_start_date', 'week_end_date']);
        });

        // Asignaciones de turnos a empleados por día de semana
        Schema::create('weekly_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_schedule_id')->constrained('weekly_schedules')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->unsignedSmallInteger('day_of_week'); // 1-7 (Monday-Sunday)

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('lunch_start_time')->nullable();
            $table->time('lunch_end_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();

            $table->timestampsTz();

            $table->unique(['weekly_schedule_id', 'employee_id', 'day_of_week'], 'ws_assignments_unique');
            $table->index(['employee_id']);
            $table->index(['weekly_schedule_id']);
        });

        // Asignaciones de turnos a equipos por día de semana (nivel macro)
        Schema::create('weekly_team_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_schedule_id')->constrained('weekly_schedules')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->unsignedSmallInteger('day_of_week'); // 1-7

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('lunch_start_time')->nullable();
            $table->time('lunch_end_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();

            $table->timestampsTz();

            $table->unique(['weekly_schedule_id', 'team_id', 'day_of_week'], 'ws_team_assignments_unique');
            $table->index(['team_id']);
            $table->index(['weekly_schedule_id']);
        });

        // ============================================
        // 5. ACTIVIDADES INTRADAY (Range-based)
        // ============================================

        Schema::create('intraday_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained('activity_types');
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL: usar tipos nativos y GiST indexes
            DB::statement('ALTER TABLE intraday_activities ADD COLUMN time_range TSTZRANGE NOT NULL');
            DB::statement('CREATE INDEX idx_intraday_activities_range ON intraday_activities USING GIST (employee_id, time_range)');
        } else {
            // SQLite/MySQL: usar STRING como fallback
            Schema::table('intraday_activities', function (Blueprint $table) {
                $table->string('time_range')->nullable();
            });
        }

        // NOTA: approved_intraday_periods se crea en migración separada (2026_05_20_160000)
        // para mantener compatibilidad con estructura de equipo vs empleado

        // ============================================
        // 6. EXCEPCIONES DE HORARIOS
        // ============================================

        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('absence_reason_code_id')->constrained('absence_reason_codes')->cascadeOnDelete();

            $table->dateTimeTz('start_at');
            $table->dateTimeTz('end_at');
            $table->boolean('is_full_day')->default(true);

            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();

            // Polimórfico: referencia origen (shift_swap_request, leave_request, etc.)
            $table->nullableMorphs('origin');

            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['employee_id', 'start_at', 'end_at']);
            $table->index(['absence_reason_code_id']);
        });

        // ============================================
        // 7. ESTADOS EN REALTIME (Unlogged para alta frecuencia)
        // ============================================

        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL UNLOGGED: mejor rendimiento, sin WAL para datos volátiles
            DB::statement('CREATE UNLOGGED TABLE agent_realtime_states (
                id BIGSERIAL PRIMARY KEY,
                employee_id BIGINT NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
                external_id VARCHAR(50) UNIQUE,
                current_state VARCHAR(50) NOT NULL,
                reason_code VARCHAR(50) NULL,
                last_changed_at TIMESTAMPTZ NOT NULL,
                metadata JSONB NULL,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )');
            DB::statement('CREATE INDEX idx_agent_realtime_employee ON agent_realtime_states (employee_id)');
            DB::statement('CREATE INDEX idx_agent_realtime_state ON agent_realtime_states (current_state)');
        } else {
            // SQLite/MySQL: tabla normal
            Schema::create('agent_realtime_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('external_id', 50)->unique()->nullable();
                $table->string('current_state', 50);
                $table->string('reason_code', 50)->nullable();
                $table->timestamp('last_changed_at');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['employee_id']);
                $table->index(['current_state']);
            });
        }
    }

    public function down(): void {
        // Orden inverso de creación
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS agent_realtime_states');
        } else {
            Schema::dropIfExists('agent_realtime_states');
        }

        Schema::dropIfExists('schedule_exceptions');
        // NOTA: approved_intraday_periods se mantiene (creada en migración 2026_05_20_160000)
        Schema::dropIfExists('intraday_activities');
        Schema::dropIfExists('weekly_team_assignments');
        Schema::dropIfExists('weekly_schedule_assignments');
        Schema::dropIfExists('weekly_schedules');
        Schema::dropIfExists('scheduled_activity_definitions');
        Schema::dropIfExists('activity_types');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('agent_states');
        Schema::dropIfExists('absence_reason_codes');
    }
};
