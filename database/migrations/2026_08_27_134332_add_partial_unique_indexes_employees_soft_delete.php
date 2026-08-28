<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing unique constraints that don't respect soft deletes
        Schema::table('employees', function ($table) {
            $table->dropUnique(['employee_number']);
            $table->dropUnique(['username']);
            $table->dropUnique(['email']);
        });

        // Create partial unique indexes that only apply to non-deleted records
        DB::statement('CREATE UNIQUE INDEX employees_employee_number_unique_active ON employees (employee_number) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX employees_username_unique_active ON employees (username) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX employees_email_unique_active ON employees (email) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partial unique indexes
        DB::statement('DROP INDEX IF EXISTS employees_employee_number_unique_active');
        DB::statement('DROP INDEX IF EXISTS employees_username_unique_active');
        DB::statement('DROP INDEX IF EXISTS employees_email_unique_active');

        // Restore original unique constraints
        Schema::table('employees', function ($table) {
            $table->unique('employee_number');
            $table->unique('username');
            $table->unique('email');
        });
    }
};
