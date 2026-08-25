<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Dos correcciones sobre audit_logs:
     *
     * 1. La FK user_id quedó con ON DELETE NO ACTION; el DDL institucional
     *    define SET NULL para conservar el log aunque se elimine el usuario.
     * 2. El trigger de inmutabilidad bloqueaba también el mantenimiento
     *    legítimo: las acciones referenciales de la FK (SET NULL) y el prune
     *    por retención. El bypass usa pg_trigger_depth() y el GUC de sesión
     *    app.audit_maintenance.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_user_id_foreign');
        DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');

        DB::statement("
            CREATE OR REPLACE FUNCTION prevent_audit_log_modification()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Acciones referenciales de FK (p.ej. ON DELETE SET NULL): permitidas.
                IF pg_trigger_depth() > 1 THEN
                    RETURN COALESCE(NEW, OLD);
                END IF;

                -- Mantenimiento explícito (prune por retención): permitido solo
                -- dentro de una transacción que active el flag de sesión.
                IF current_setting('app.audit_maintenance', true) = 'on' THEN
                    RETURN COALESCE(NEW, OLD);
                END IF;

                RAISE EXCEPTION 'audit_logs es inmutable: no se permite UPDATE ni DELETE';
            END;
            \$\$ LANGUAGE plpgsql
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_user_id_foreign');
        DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id)');

        DB::statement("
            CREATE OR REPLACE FUNCTION prevent_audit_log_modification()
            RETURNS TRIGGER AS \$\$
            BEGIN
                RAISE EXCEPTION 'audit_logs es inmutable: no se permite UPDATE ni DELETE';
            END;
            \$\$ LANGUAGE plpgsql
        ");
    }
};
