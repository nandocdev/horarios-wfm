<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('skill_id')->constrained('skills');
            $table->unsignedSmallInteger('level')->default(1)->comment('Nivel de 1 a 5');
            $table->decimal('years_experience', 4, 1)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->date('certified_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'skill_id']);
            $table->index('level');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skills');
    }
};
