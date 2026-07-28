<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_intervals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('forecast_scenario_id')->constrained('forecast_scenarios');
            $table->timestamp('interval_start');
            $table->timestamp('interval_end');
            $table->unsignedSmallInteger('interval_minutes')->default(15);
            $table->unsignedInteger('call_volume_forecast')->default(0);
            $table->unsignedInteger('talk_time_seconds_forecast')->default(0);
            $table->unsignedDecimal('aht_seconds_forecast', 10, 2)->default(0);
            $table->unsignedDecimal('staff_required', 10, 2)->default(0)->comment('Agentes FTE requeridos');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['forecast_scenario_id', 'interval_start'], 'forecast_intervals_scenario_interval_unique');
            $table->index('interval_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_intervals');
    }
};
