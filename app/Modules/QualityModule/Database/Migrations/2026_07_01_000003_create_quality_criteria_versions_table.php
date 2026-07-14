<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_criteria_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('criteria_id')->constrained('quality_criteria')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('criterio_text');
            $table->unsignedSmallInteger('puntaje');
            $table->text('descripcion')->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['criteria_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_criteria_versions');
    }
};
