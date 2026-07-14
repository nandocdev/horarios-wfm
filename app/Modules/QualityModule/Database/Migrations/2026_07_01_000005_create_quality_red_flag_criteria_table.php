<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_red_flag_criteria', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('criterio_text');
            $table->unsignedSmallInteger('perdida')->comment('Puntos que descuenta del score total');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_red_flag_criteria');
    }
};
