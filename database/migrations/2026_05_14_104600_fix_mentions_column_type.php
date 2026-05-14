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
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE mentions ALTER COLUMN mentionable_id TYPE BIGINT USING mentionable_id::bigint');
        } else {
            Schema::table('mentions', function (Blueprint $table) {
                $table->unsignedBigInteger('mentionable_id')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentions', function (Blueprint $table) {
            $table->string('mentionable_id')->change();
        });
    }
};
