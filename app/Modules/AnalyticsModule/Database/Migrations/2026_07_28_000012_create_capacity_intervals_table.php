<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_intervals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('capacity_plan_id')->constrained('capacity_plans');
            $table->timestamp('interval_start');
            $table->timestamp('interval_end');
            $table->unsignedSmallInteger('interval_minutes')->default(15);
            $table->string('queue_id', 100)->comment('Identificador de la cola/CSQ');
            $table->unsignedInteger('forecast_call_volume')->default(0);
            $table->decimal('forecast_aht', 10, 2)->default(0);
            $table->decimal('staff_required', 10, 2)->default(0)->comment('Agentes requeridos por workload');
            $table->decimal('staff_scheduled', 10, 2)->default(0)->comment('Agentes programados');
            $table->decimal('staff_available', 10, 2)->default(0)->comment('Disponibles tras shrinkage');
            $table->decimal('staff_with_skill', 10, 2)->default(0)->comment('Programados con skill requerido');
            $table->decimal('coverage', 5, 2)->default(0)->comment('(available / required) * 100');
            $table->decimal('gap', 10, 2)->default(0)->comment('required - available');
            $table->decimal('skill_gap', 10, 2)->default(0)->comment('required - staff_with_skill');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['capacity_plan_id', 'interval_start', 'queue_id'], 'capacity_interval_plan_queue_unique');
            $table->index('interval_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_intervals');
    }
};
