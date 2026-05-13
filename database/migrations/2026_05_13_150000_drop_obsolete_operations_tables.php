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
        // Eliminamos las tablas obsoletas que fueron reemplazadas por el nuevo motor de asistencia
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('attendance');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-crear las tablas si es necesario, aunque perdamos los datos (eran placeholders)
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('check_in')->nullable();
            $table->timestampTz('check_out')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }
};
