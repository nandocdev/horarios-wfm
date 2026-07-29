<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_employee_snapshot', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('position_id')->nullable()->constrained('positions');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees');
            $table->foreignId('employment_status_id')->nullable()->constrained('employment_statuses');
            $table->boolean('is_active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('is_current');
            $table->index('valid_from');
            $table->index('valid_to');
            $table->index(['employee_id', 'is_current']);
            $table->index(['valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_employee_snapshot');
    }
};
