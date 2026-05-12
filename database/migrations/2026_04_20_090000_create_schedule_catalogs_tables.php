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
        // 1. CODIGOS DE AUSENCIA / JUSTIFICACIÓN
        Schema::create('absence_reason_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('short_code', 10)->unique();
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_excused')->default(true);
            $table->string('color', 20)->nullable();
            $table->timestampsTz();
        });

        // 2. ESTADOS DE AGENTE (MAPEO API EXTERNA)
        Schema::create('agent_states', function (Blueprint $table) {
            $table->id();
            $table->string('external_code', 50)->unique(); // Código que devuelve Cisco/API
            $table->string('display_name', 100);
            $table->boolean('is_productive')->default(false);
            $table->string('color_hex', 7)->default('#cbd5e1');
            $table->timestampsTz();
        });

        // 3. DEFINICIONES DE ACTIVIDADES PROGRAMADAS (TEMPLATES)
        Schema::create('scheduled_activity_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('activity_type_id')->constrained('activity_types')->cascadeOnDelete();
            $table->integer('default_duration_minutes')->nullable();
            $table->string('default_location')->nullable();
            $table->string('default_instructor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_activity_definitions');
        Schema::dropIfExists('agent_states');
        Schema::dropIfExists('absence_reason_codes');
    }
};
