<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Necesario para constraints EXCLUDE en PostgreSQL (e.g. solapamientos intradía)
        // Evitar ejecutar en entornos SQLite (tests) — solo para driver pgsql
        try {
            $driver = DB::getDriverName();
        } catch (Throwable $e) {
            $driver = config('database.default');
        }

        if ($driver === 'pgsql' || $driver === 'postgresql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            $driver = DB::getDriverName();
        } catch (Throwable $e) {
            $driver = config('database.default');
        }

        if ($driver === 'pgsql' || $driver === 'postgresql') {
            DB::statement('DROP EXTENSION IF EXISTS btree_gist;');
        }
    }
};
