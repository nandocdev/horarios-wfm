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
        // 1. Team Members (Historial)
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('joined_at')->useCurrent();
            $table->date('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'employee_id', 'joined_at']);
        });

        // 2. Employee Positions (Historial)
        Schema::create('employee_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('position_id')->constrained()->onDelete('cascade');
            $table->date('start_date')->useCurrent();
            $table->date('end_date')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'position_id', 'start_date']);
        });

        // 3. Employee Dependents
        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('relationship', 50);
            $table->date('birth_date');
            $table->timestamps();
        });

        // 4. Employee Diseases
        Schema::create('employee_diseases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('disease_type_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Employee Disabilities
        Schema::create('employee_disabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('disability_type_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_disabilities');
        Schema::dropIfExists('employee_diseases');
        Schema::dropIfExists('employee_dependents');
        Schema::dropIfExists('employee_positions');
        Schema::dropIfExists('team_members');
    }
};
