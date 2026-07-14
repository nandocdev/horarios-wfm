<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_calibration_log', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('evaluation_id')->constrained('quality_evaluations')->cascadeOnDelete();
            $table->unsignedSmallInteger('score_anterior');
            $table->unsignedSmallInteger('score_nuevo');
            $table->text('obs')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_calibration_log');
    }
};
