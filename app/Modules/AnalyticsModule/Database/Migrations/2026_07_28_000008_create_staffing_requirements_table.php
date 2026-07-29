<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staffing_requirements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamp('interval_start');
            $table->timestamp('interval_end');
            $table->unsignedSmallInteger('interval_minutes')->default(15);
            $table->string('queue_id', 100)->comment('Identificador de la cola/CSQ');
            $table->decimal('required_agents', 10, 2)->default(0)->comment('Agentes requeridos por forecast');
            $table->decimal('scheduled_agents', 10, 2)->default(0)->comment('Agentes programados en WFM');
            $table->decimal('available_agents', 10, 2)->default(0)->comment('Agentes disponibles tras shrinkage');
            $table->decimal('coverage', 5, 2)->default(0)->comment('(available / required) * 100');
            $table->decimal('gap', 10, 2)->default(0)->comment('required - available, positivo = shortage');
            $table->decimal('shrinkage_rate', 5, 2)->default(0)->comment('Porcentaje de shrinkage aplicado');
            $table->string('forecast_version_id')->nullable()->comment('ID de la versión de forecast usada');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['interval_start', 'queue_id'], 'staffing_req_interval_queue_unique');
            $table->index('queue_id');
            $table->index('interval_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staffing_requirements');
    }
};
