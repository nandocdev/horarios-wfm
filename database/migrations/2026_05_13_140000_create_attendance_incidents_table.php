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
        // Crear attendance_incidents
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
    }
};
