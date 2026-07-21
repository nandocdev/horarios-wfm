<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_operator_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('employee_id');
            $table->date('report_date');

            // Schedule snapshot
            $table->time('scheduled_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            // State durations (seconds)
            $table->integer('talk_seconds')->default(0);
            $table->integer('ready_seconds')->default(0);
            $table->integer('acw_seconds')->default(0);
            $table->integer('reserved_seconds')->default(0);
            $table->integer('not_ready_seconds')->default(0);
            $table->integer('lunch_seconds')->default(0);
            $table->integer('break_seconds')->default(0);
            $table->integer('offline_seconds')->default(0);

            // Call metrics
            $table->integer('total_calls')->default(0);
            $table->integer('handled_calls')->default(0);
            $table->integer('abandoned_calls')->default(0);
            $table->integer('total_talk_seconds')->default(0);
            $table->integer('total_hold_seconds')->default(0);
            $table->integer('total_work_seconds')->default(0);

            // Pre-calculated KPIs
            $table->decimal('adherence_pct', 5, 2)->nullable();
            $table->decimal('occupancy_pct', 5, 2)->nullable();
            $table->decimal('productivity_pct', 5, 2)->nullable();
            $table->decimal('avg_handle_time', 10, 2)->nullable();

            // Metadata
            $table->integer('exception_count')->default(0);
            $table->boolean('has_exceptions')->default(false);
            $table->time('real_entry')->nullable();
            $table->integer('entry_diff_minutes')->nullable();
            $table->boolean('is_complete')->default(false);

            $table->timestamps();

            $table->unique(['employee_id', 'report_date']);
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_operator_reports');
    }
};
