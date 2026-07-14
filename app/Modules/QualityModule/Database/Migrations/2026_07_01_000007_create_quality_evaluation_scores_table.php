<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_evaluation_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('evaluation_id')->constrained('quality_evaluations')->cascadeOnDelete();
            $table->foreignUlid('criteria_version_id')->constrained('quality_criteria_versions');
            $table->unsignedSmallInteger('puntaje_obtenido');
            $table->timestamps();

            $table->unique(['evaluation_id', 'criteria_version_id'], 'uq_eval_criteria_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_evaluation_scores');
    }
};
