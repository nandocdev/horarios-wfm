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
        // 1. Actualizar tabla de solicitudes con Snapshots
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->jsonb('requester_assignment_snapshot')->nullable()->after('reason');
            $table->jsonb('recipient_assignment_snapshot')->nullable()->after('requester_assignment_snapshot');
        });

        // 2. Actualizar tabla de asignaciones para trazabilidad e inmutabilidad
        Schema::table('weekly_schedule_assignments', function (Blueprint $table) {
            $table->foreignId('swap_request_id')->nullable()->constrained('shift_swap_requests')->nullOnDelete();
            $table->boolean('is_replaced')->default(false);
            $table->timestampTz('replaced_at')->nullable();

            // Eliminar restricción única anterior para permitir históricos
            $table->dropUnique('ws_assignments_unique');
        });

        // 3. Crear índice único parcial para asegurar que solo haya UNA asignación activa por empleado/día
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX ws_assignments_active_unique 
                ON weekly_schedule_assignments (weekly_schedule_id, employee_id, day_of_week) 
                WHERE (is_replaced = false)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS ws_assignments_active_unique');
        }

        Schema::table('weekly_schedule_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('swap_request_id');
            $table->dropColumn(['is_replaced', 'replaced_at']);

            // Restaurar restricción original (advertencia: esto fallará si hay duplicados históricos)
            $table->unique(['weekly_schedule_id', 'employee_id', 'day_of_week'], 'ws_assignments_unique');
        });

        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->dropColumn(['requester_assignment_snapshot', 'recipient_assignment_snapshot']);
        });
    }
};
