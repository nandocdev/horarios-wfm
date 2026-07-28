<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_interval_id')->nullable();
            $table->unsignedBigInteger('dim_employee_id')->nullable();
            $table->unsignedBigInteger('dim_queue_id')->nullable();
            $table->unsignedBigInteger('dim_team_id')->nullable();
            $table->unsignedBigInteger('dim_department_id')->nullable();
            $table->unsignedBigInteger('source_call_id');
            $table->unsignedSmallInteger('talk_seconds')->default(0);
            $table->unsignedSmallInteger('hold_seconds')->default(0);
            $table->unsignedSmallInteger('wrap_seconds')->default(0);
            $table->unsignedSmallInteger('ring_seconds')->default(0);
            $table->unsignedSmallInteger('queue_seconds')->default(0);
            $table->unsignedSmallInteger('handle_seconds')->default(0)->comment('talk + hold + wrap');
            $table->boolean('is_abandoned')->default(false);
            $table->boolean('is_handled')->default(false);
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_employee_id');
            $table->index('dim_queue_id');
            $table->index('source_call_id');
        });

        Schema::create('fact_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_interval_id');
            $table->unsignedBigInteger('dim_employee_id');
            $table->unsignedBigInteger('dim_team_id')->nullable();
            $table->unsignedBigInteger('dim_department_id')->nullable();
            $table->unsignedBigInteger('dim_shift_id')->nullable();
            $table->time('scheduled_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->unsignedSmallInteger('scheduled_minutes')->default(0);
            $table->unsignedSmallInteger('lunch_minutes')->default(0);
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->boolean('is_off')->default(false);
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_employee_id');
            $table->index(['dim_date_id', 'dim_employee_id']);
        });

        Schema::create('fact_quality', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_employee_id');
            $table->unsignedBigInteger('dim_queue_id')->nullable();
            $table->unsignedBigInteger('dim_team_id')->nullable();
            $table->unsignedBigInteger('source_evaluation_id');
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->decimal('score_pct', 5, 2)->nullable();
            $table->boolean('has_redflag')->default(false);
            $table->unsignedBigInteger('evaluator_id')->nullable();
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_employee_id');
        });

        Schema::create('fact_absence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_employee_id');
            $table->unsignedBigInteger('dim_team_id')->nullable();
            $table->unsignedBigInteger('dim_department_id')->nullable();
            $table->unsignedBigInteger('source_exception_id')->nullable();
            $table->unsignedBigInteger('source_leave_id')->nullable();
            $table->string('reason_name', 100);
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->boolean('is_full_day')->default(false);
            $table->boolean('is_excused')->default(true);
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_employee_id');
            $table->index('dim_team_id');
        });

        Schema::create('fact_agent_interval', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_interval_id');
            $table->unsignedBigInteger('dim_employee_id');
            $table->unsignedBigInteger('dim_team_id')->nullable();
            $table->unsignedBigInteger('dim_department_id')->nullable();
            $table->unsignedSmallInteger('talk_seconds')->default(0);
            $table->unsignedSmallInteger('hold_seconds')->default(0);
            $table->unsignedSmallInteger('ready_seconds')->default(0);
            $table->unsignedSmallInteger('not_ready_seconds')->default(0);
            $table->unsignedSmallInteger('wrap_seconds')->default(0);
            $table->unsignedSmallInteger('calls_handled')->default(0);
            $table->decimal('aht_seconds', 10, 2)->default(0);
            $table->decimal('occupancy', 5, 2)->default(0);
            $table->decimal('utilization', 5, 2)->default(0);
            $table->decimal('adherence', 5, 2)->default(0);
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_employee_id');
            $table->index(['dim_date_id', 'dim_employee_id']);
        });

        Schema::create('fact_forecast', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_interval_id');
            $table->unsignedBigInteger('dim_queue_id');
            $table->string('forecast_version_id', 50)->nullable();
            $table->unsignedInteger('call_volume_forecast')->default(0);
            $table->unsignedInteger('talk_seconds_forecast')->default(0);
            $table->decimal('aht_seconds_forecast', 10, 2)->default(0);
            $table->decimal('staff_required_forecast', 10, 2)->default(0);
            $table->unsignedInteger('actual_call_volume')->nullable();
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_queue_id');
            $table->index('forecast_version_id');
        });

        Schema::create('fact_staffing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dim_date_id');
            $table->unsignedBigInteger('dim_interval_id');
            $table->unsignedBigInteger('dim_queue_id');
            $table->decimal('required_agents', 10, 2)->default(0);
            $table->decimal('scheduled_agents', 10, 2)->default(0);
            $table->decimal('available_agents', 10, 2)->default(0);
            $table->decimal('coverage', 5, 2)->default(0);
            $table->decimal('gap', 10, 2)->default(0);
            $table->timestamps();

            $table->index('dim_date_id');
            $table->index('dim_queue_id');
            $table->index(['dim_date_id', 'dim_queue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_staffing');
        Schema::dropIfExists('fact_forecast');
        Schema::dropIfExists('fact_agent_interval');
        Schema::dropIfExists('fact_absence');
        Schema::dropIfExists('fact_quality');
        Schema::dropIfExists('fact_schedule');
        Schema::dropIfExists('fact_calls');
    }
};
