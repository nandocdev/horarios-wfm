<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_evaluation_red_flags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('evaluation_id')->constrained('quality_evaluations')->cascadeOnDelete();
            $table->foreignUlid('red_flag_criteria_id')->constrained('quality_red_flag_criteria');
            $table->timestamps();

            $table->unique(['evaluation_id', 'red_flag_criteria_id'], 'uq_eval_redflag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_evaluation_red_flags');
    }
};
