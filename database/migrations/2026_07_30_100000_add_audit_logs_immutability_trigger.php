<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE OR REPLACE FUNCTION prevent_audit_log_modification()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION \'audit_logs es inmutable: no se permite UPDATE ni DELETE\';
            END;
            $$ LANGUAGE plpgsql
        ');

        DB::statement('
            CREATE TRIGGER trg_audit_logs_immutable
                BEFORE UPDATE OR DELETE ON audit_logs
                FOR EACH ROW
                EXECUTE FUNCTION prevent_audit_log_modification()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_audit_logs_immutable ON audit_logs');
        DB::statement('DROP FUNCTION IF EXISTS prevent_audit_log_modification');
    }
};
