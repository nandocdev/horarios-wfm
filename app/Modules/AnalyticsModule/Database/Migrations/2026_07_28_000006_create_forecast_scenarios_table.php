<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_scenarios', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('forecast_version_id')->constrained('forecast_versions');
            $table->string('name');
            $table->string('scenario_type', 50)->default('base')->comment('base, optimistic, pessimistic, what_if');
            $table->decimal('multiplier', 5, 2)->default(1.00)->comment('Factor de ajuste sobre el escenario base');
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('scenario_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_scenarios');
    }
};
