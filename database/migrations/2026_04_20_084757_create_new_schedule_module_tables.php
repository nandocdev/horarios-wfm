<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. CONFIGURACIÓN BASE DE POSTGRES
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        }

        // 2. TABLA DE TURNOS (SHIFT DEFINITIONS)
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
        });

        // 3. PLANIFICACIÓN SEMANAL (CONTAINER)
        Schema::create('weekly_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('week_start_date'); // Lunes
            $table->date('week_end_date');           // Domingo
            $table->string('status', 20)->default('draft'); // draft, published, closed
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
        });

        // Índice parcial: solo un publicado por semana, pero múltiples drafts permitidos
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX weekly_schedules_published_unique ON weekly_schedules (week_start_date) WHERE (status = ''published'')');
        }

        // 4. ASIGNACIONES SEMANALES (THE GRID)
        Schema::create('weekly_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_schedule_id')->constrained('weekly_schedules')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->unsignedSmallInteger('day_of_week'); // 1-7
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('lunch_start_time')->nullable();
            $table->time('lunch_end_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->timestampsTz();
        });

        // 5. ASIGNACIONES DE EQUIPO (MACRO)
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
        });

        // 5. ASIGNACIONES INTRADAY (Breaks, Meetings, etc.)
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('color', 20)->nullable();
            $table->boolean('is_productive')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->timestampsTz();
        });

        Schema::create('intraday_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained('activity_types');
            $table->unsignedBigInteger('approved_period_id')->nullable()->after('activity_type_id');
            $table->text('notes')->nullable();
            $table->index('approved_period_id');
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE intraday_activities ADD COLUMN time_range TSTZRANGE NOT NULL');
            DB::statement('ALTER TABLE intraday_activities ADD CONSTRAINT intraday_no_overlap EXCLUDE USING GIST (employee_id WITH =, time_range WITH &&)');
        } else {
            Schema::table('intraday_activities', function (Blueprint $table) {
                $table->string('time_range')->nullable();
            });
        }

        // 6. MONITOREO (TABLA UNLOGGED PARA ESTADOS)
        if (DB::getDriverName() === 'pgsql') {
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
            Schema::create('agent_realtime_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('external_id', 50)->unique()->nullable();
                $table->string('current_state', 50);
                $table->string('reason_code', 50)->nullable();
                $table->timestamp('last_changed_at');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // Columns for swap traceability (no FK constraint - shift_swap_requests created in later migration)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE weekly_schedule_assignments ADD COLUMN swap_request_id BIGINT NULL');
            DB::statement('ALTER TABLE weekly_schedule_assignments ADD COLUMN is_replaced BOOLEAN NOT NULL DEFAULT false');
            DB::statement('ALTER TABLE weekly_schedule_assignments ADD COLUMN replaced_at TIMESTAMPTZ NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS ws_assignments_active_unique ON weekly_schedule_assignments (weekly_schedule_id, employee_id, day_of_week) WHERE (is_replaced = false)');
        } else {
            Schema::table('weekly_schedule_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('swap_request_id')->nullable();
                $table->boolean('is_replaced')->default(false);
                $table->timestampTz('replaced_at')->nullable();
            });
        }

        // Index for schedule assignment lookups
        Schema::table('weekly_schedule_assignments', function (Blueprint $table) {
            $table->index(['weekly_schedule_id', 'employee_id', 'day_of_week'], 'idx_weekly_schedule_assignments_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop swap columns from weekly_schedule_assignments
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS ws_assignments_active_unique');
        }
        Schema::table('weekly_schedule_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_weekly_schedule_assignments_lookup');
            $table->dropColumn(['swap_request_id', 'is_replaced', 'replaced_at']);
        });

        DB::statement('DROP TABLE IF EXISTS agent_realtime_states');
        Schema::dropIfExists('intraday_activities');
        Schema::dropIfExists('activity_types');
        Schema::dropIfExists('weekly_schedule_assignments');
        Schema::dropIfExists('weekly_schedules');
        Schema::dropIfExists('schedules');
    }
};
