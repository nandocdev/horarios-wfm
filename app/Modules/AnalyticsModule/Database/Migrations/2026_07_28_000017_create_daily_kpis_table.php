<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_kpis', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('evaluation_date');
            $table->string('granularity', 20)->comment('global, team, queue, employee');
            $table->unsignedBigInteger('dim_employee_id')->nullable();
            $table->unsignedBigInteger('dim_team_id')->nullable();
            $table->unsignedBigInteger('dim_queue_id')->nullable();
            $table->decimal('occupancy', 5, 2)->nullable()->comment('(talk+hold+wrap)/(talk+hold+wrap+ready)*100');
            $table->decimal('utilization', 5, 2)->nullable()->comment('(productive_time/logged_time)*100');
            $table->decimal('adherence', 5, 2)->nullable()->comment('Schedule adherence %');
            $table->decimal('aht_seconds', 10, 2)->nullable()->comment('Average Handle Time');
            $table->decimal('asa_seconds', 10, 2)->nullable()->comment('Average Speed of Answer');
            $table->decimal('service_level', 5, 2)->nullable()->comment('% calls answered within threshold');
            $table->decimal('shrinkage_pct', 5, 2)->nullable()->comment('Shrinkage % del día');
            $table->decimal('forecast_accuracy_pct', 5, 2)->nullable()->comment('100-MAPE del forecast vs actual');
            $table->decimal('quality_score', 5, 2)->nullable()->comment('Promedio evaluaciones de calidad');
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('total_talk_seconds')->default(0);
            $table->unsignedInteger('total_hold_seconds')->default(0);
            $table->unsignedInteger('total_wrap_seconds')->default(0);
            $table->unsignedInteger('total_ready_seconds')->default(0);
            $table->unsignedInteger('total_not_ready_seconds')->default(0);
            $table->unsignedInteger('total_login_seconds')->default(0);
            $table->unsignedInteger('total_scheduled_minutes')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_date', 'granularity', 'dim_employee_id', 'dim_team_id', 'dim_queue_id'], 'daily_kpis_unique');
            $table->index('evaluation_date');
            $table->index('granularity');
            $table->index('dim_employee_id');
            $table->index('dim_team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_kpis');
    }
};
