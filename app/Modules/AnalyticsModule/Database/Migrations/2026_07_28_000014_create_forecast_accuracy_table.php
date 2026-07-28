<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_accuracy', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('forecast_version_id')->nullable()->comment('ID de la versión de forecast evaluada');
            $table->string('forecast_scenario_id')->nullable();
            $table->string('queue_id', 100);
            $table->date('evaluation_date');
            $table->unsignedInteger('forecast_call_volume')->default(0);
            $table->unsignedInteger('actual_call_volume')->default(0);
            $table->decimal('forecast_aht', 10, 2)->default(0);
            $table->decimal('actual_aht', 10, 2)->default(0);
            $table->integer('volume_error')->default(0)->comment('forecast - actual');
            $table->unsignedInteger('volume_abs_error')->default(0)->comment('|forecast - actual|');
            $table->decimal('volume_ape', 5, 2)->default(0)->comment('APE % del volumen');
            $table->decimal('mape', 5, 2)->default(0)->comment('Mean Absolute Percentage Error');
            $table->decimal('bias', 5, 2)->default(0)->comment('(forecast - actual) / actual * 100');
            $table->decimal('rmse', 10, 2)->default(0)->comment('Root Mean Square Error');
            $table->decimal('accuracy', 5, 2)->default(0)->comment('100 - MAPE');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('forecast_version_id');
            $table->index(['evaluation_date', 'queue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_accuracy');
    }
};
