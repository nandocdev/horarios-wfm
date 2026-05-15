<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En Postgres, cambiar de VARCHAR a BIGINT requiere un cast explícito si hay datos.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE categorizables ALTER COLUMN categorizable_id TYPE BIGINT USING categorizable_id::bigint');
            DB::statement('ALTER TABLE taggables ALTER COLUMN taggable_id TYPE BIGINT USING taggable_id::bigint');
        } else {
            Schema::table('categorizables', function (Blueprint $table) {
                $table->bigInteger('categorizable_id')->change();
            });
            Schema::table('taggables', function (Blueprint $table) {
                $table->bigInteger('taggable_id')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorizables', function (Blueprint $table) {
            $table->string('categorizable_id')->change();
        });
        Schema::table('taggables', function (Blueprint $table) {
            $table->string('taggable_id')->change();
        });
    }
};
