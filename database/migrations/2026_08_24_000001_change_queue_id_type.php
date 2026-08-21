<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_events', function (Blueprint $table) {
            // Convertir queue_id de varchar a bigint usando USING
            DB::statement('ALTER TABLE alert_events ALTER COLUMN queue_id TYPE bigint USING queue_id::bigint');
            $table->unsignedBigInteger('queue_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('alert_events', function (Blueprint $table) {
            // Revertir a varchar si es necesario
            DB::statement('ALTER TABLE alert_events ALTER COLUMN queue_id TYPE varchar USING queue_id::varchar');
            $table->string('queue_id')->nullable()->change();
        });
    }
};