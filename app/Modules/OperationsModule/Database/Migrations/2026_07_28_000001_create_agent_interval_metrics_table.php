<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_interval_metrics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('employee_id')->constrained('employees');
            $table->timestamp('interval_start');
            $table->timestamp('interval_end');
            $table->unsignedInteger('talk_seconds')->default(0);
            $table->unsignedInteger('hold_seconds')->default(0);
            $table->unsignedInteger('ready_seconds')->default(0);
            $table->unsignedInteger('not_ready_seconds')->default(0);
            $table->unsignedInteger('wrap_seconds')->default(0);
            $table->unsignedSmallInteger('calls_handled')->default(0);
            $table->decimal('aht_seconds', 10, 2)->default(0);
            $table->decimal('occupancy', 5, 2)->default(0)->comment('Porcentaje');
            $table->decimal('utilization', 5, 2)->default(0)->comment('Porcentaje');
            $table->decimal('adherence', 5, 2)->default(0)->comment('Porcentaje');
            $table->jsonb('queue_distribution')->nullable()->comment('Distribución por cola dentro del intervalo');
            $table->timestamps();

            $table->unique(['employee_id', 'interval_start']);
            $table->index('interval_start');
            $table->index(['employee_id', 'interval_start', 'interval_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_interval_metrics');
    }
};
