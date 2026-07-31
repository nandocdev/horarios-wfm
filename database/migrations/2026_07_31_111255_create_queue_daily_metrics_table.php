<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_daily_metrics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('queue_id')->constrained('call_queues')->cascadeOnDelete();
            $table->date('metric_date');

            $table->integer('offered_calls')->default(0);
            $table->integer('handled_calls')->default(0);
            $table->integer('abandoned_calls')->default(0);
            $table->integer('sl_calls')->default(0);

            $table->integer('total_talk_seconds')->default(0);
            $table->integer('total_work_seconds')->default(0);
            $table->integer('total_hold_seconds')->default(0);
            $table->integer('total_wait_seconds')->default(0);
            $table->integer('max_wait_seconds')->default(0);
            $table->integer('min_wait_seconds')->default(0);
            $table->integer('total_abandon_seconds')->default(0);

            $table->timestamps();

            $table->unique(['queue_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_daily_metrics');
    }
};
