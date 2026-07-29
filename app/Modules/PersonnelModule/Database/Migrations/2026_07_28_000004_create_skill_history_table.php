<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_skill_id')->nullable()->constrained('employee_skills');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('skill_id')->constrained('skills');
            $table->unsignedSmallInteger('old_level')->nullable();
            $table->unsignedSmallInteger('new_level');
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('skill_id');
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_history');
    }
};
