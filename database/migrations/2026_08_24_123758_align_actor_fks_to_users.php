<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alinea las columnas que referencian actores (supervisor, requester, approver,
     * created_by) con users.id, según la decisión arquitectónica institucional:
     * "actor = usuario". Los valores que aún guardan employees.id se remapean a su
     * user_id cuando existe correspondencia; el resto queda NULL para no violar FKs.
     */
    public function up(): void
    {
        $columns = [
            'teams' => 'supervisor_id',
            'schedule_exceptions' => 'created_by',
            'workflow_requests' => 'requester_id',
            'workflow_approvals' => 'approver_id',
            'workflow_delegations' => 'original_approver_id',
        ];

        foreach ($columns as $table => $column) {
            // 1) Remapear employees.id -> users.id solo cuando el valor no sea ya un user válido.
            DB::statement(sprintf(
                'UPDATE %1$s t SET %2$s = (SELECT e.user_id FROM employees e WHERE e.id = t.%2$s)
                 WHERE t.%2$s IS NOT NULL
                   AND EXISTS (SELECT 1 FROM employees e WHERE e.id = t.%2$s)
                   AND NOT EXISTS (SELECT 1 FROM users u WHERE u.id = t.%2$s)',
                $table,
                $column
            ));

            // 2) Anular referencias sin correspondencia a users para permitir la FK.
            DB::statement(sprintf(
                'UPDATE %1$s t SET %2$s = NULL
                 WHERE t.%2$s IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.id = t.%2$s)',
                $table,
                $column
            ));

            // 3) Reescribir la FK hacia users(id).
            DB::statement(sprintf(
                'ALTER TABLE %1$s DROP CONSTRAINT IF EXISTS %1$s_%2$s_foreign',
                $table,
                $column
            ));
            DB::statement(sprintf(
                'ALTER TABLE %1$s ADD CONSTRAINT %1$s_%2$s_foreign FOREIGN KEY (%2$s) REFERENCES users(id) ON DELETE SET NULL',
                $table,
                $column
            ));
        }
    }

    public function down(): void
    {
        // La reversión del mapeo employee<->user es destructiva (no se preserva el id original);
        // se restauran únicamente los constraints hacia employees por completitud del down().
        $columns = [
            'teams' => 'supervisor_id',
            'schedule_exceptions' => 'created_by',
            'workflow_requests' => 'requester_id',
            'workflow_approvals' => 'approver_id',
            'workflow_delegations' => 'original_approver_id',
        ];

        foreach ($columns as $table => $column) {
            DB::statement(sprintf(
                'ALTER TABLE %1$s DROP CONSTRAINT IF EXISTS %1$s_%2$s_foreign',
                $table,
                $column
            ));
            DB::statement(sprintf(
                'ALTER TABLE %1$s ADD CONSTRAINT %1$s_%2$s_foreign FOREIGN KEY (%2$s) REFERENCES employees(id) ON DELETE SET NULL',
                $table,
                $column
            ));
        }
    }
};
