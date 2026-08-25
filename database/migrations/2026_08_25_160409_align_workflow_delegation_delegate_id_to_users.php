<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alinea workflow_delegations.delegate_id con users.id, según la decisión
     * arquitectónica "actor = usuario". La migración 2026_08_24_123758 solo
     * reescribió original_approver_id y omitió esta columna.
     *
     * delegate_id es NOT NULL: las delegaciones sin correspondencia a un user
     * se eliminan, pues no tendrían destinatario válido.
     */
    public function up(): void
    {
        // 1) Remapear employees.id -> users.id solo cuando el valor no sea ya un user válido.
        DB::statement(
            'UPDATE workflow_delegations t SET delegate_id = (SELECT e.user_id FROM employees e WHERE e.id = t.delegate_id)
             WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = t.delegate_id)
               AND EXISTS (SELECT 1 FROM employees e WHERE e.id = t.delegate_id AND e.user_id IS NOT NULL)'
        );

        // 2) Eliminar delegaciones sin correspondencia a users para permitir la FK.
        DB::statement(
            'DELETE FROM workflow_delegations t
             WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = t.delegate_id)'
        );

        // 3) Reescribir la FK hacia users(id).
        DB::statement('ALTER TABLE workflow_delegations DROP CONSTRAINT IF EXISTS workflow_delegations_delegate_id_foreign');
        DB::statement('ALTER TABLE workflow_delegations ADD CONSTRAINT workflow_delegations_delegate_id_foreign FOREIGN KEY (delegate_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE workflow_delegations DROP CONSTRAINT IF EXISTS workflow_delegations_delegate_id_foreign');
        DB::statement('ALTER TABLE workflow_delegations ADD CONSTRAINT workflow_delegations_delegate_id_foreign FOREIGN KEY (delegate_id) REFERENCES employees(id) ON DELETE CASCADE');
    }
};
