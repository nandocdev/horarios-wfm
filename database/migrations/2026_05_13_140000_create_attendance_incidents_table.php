<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Corregir incident_types.color para que acepte hex o más opciones
        // En PostgreSQL, el enum crea un check constraint que debemos eliminar si queremos cambiar a string
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE incident_types DROP CONSTRAINT IF EXISTS incident_types_color_check');
        }

        Schema::table('incident_types', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->change()->default('#3b82f6'); // Blue 500
        });

        // 2. Crear attendance_incidents
        Schema::create('attendance_incidents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_type_id')->constrained('incident_types')->cascadeOnDelete();
            $table->date('incident_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('user_comment')->nullable();
            $table->text('admin_comment')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'incident_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_incidents');

        Schema::table('incident_types', function (Blueprint $table) {
            // Revertir a enum es difícil en una migración down sin conocer los datos previos,
            // pero para desarrollo podemos dejarlo como string o intentar revertirlo si es necesario.
        });
    }
};
