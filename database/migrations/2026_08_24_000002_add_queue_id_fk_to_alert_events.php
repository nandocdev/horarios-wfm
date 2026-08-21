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
            // Agregar constraint FK - la columna ya es bigint por la migración anterior
            DB::statement('ALTER TABLE alert_events ADD CONSTRAINT alert_events_queue_id_foreign FOREIGN KEY (queue_id) REFERENCES call_queues(id) ON DELETE SET NULL');
        });
    }

    public function down(): void
    {
        Schema::table('alert_events', function (Blueprint $table) {
            DB::statement('ALTER TABLE alert_events DROP CONSTRAINT IF EXISTS alert_events_queue_id_foreign');
        });
    }
};