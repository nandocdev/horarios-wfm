<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('csq_realtime_stats', function (Blueprint $table) {
            $table->id();
            $table->string('csq_name')->unique();

            // Snapshot metrics
            $table->integer('calls_waiting')->default(0);
            $table->integer('longest_call_in_queue')->default(0); // in seconds

            // Agent states distribution
            $table->integer('agents_logged_on')->default(0);
            $table->integer('agents_talking')->default(0);
            $table->integer('agents_ready')->default(0);
            $table->integer('agents_not_ready')->default(0);
            $table->integer('agents_after_call_work')->default(0);
            $table->integer('agents_reserved')->default(0);

            // Service Levels
            $table->decimal('service_level_short_term', 5, 2)->default(0);
            $table->decimal('service_level_long_term', 5, 2)->default(0);

            // Cumulative since midnight
            $table->integer('calls_abandoned_since_midnight')->default(0);
            $table->integer('calls_handled_since_midnight')->default(0);
            $table->integer('total_calls_since_midnight')->default(0);

            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csq_realtime_stats');
    }
};
