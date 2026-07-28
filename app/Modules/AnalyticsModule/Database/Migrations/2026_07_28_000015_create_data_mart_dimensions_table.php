<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dim_employee', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->unique();
            $table->string('employee_number', 20);
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('team_name')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('position_name')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_manager')->default(false);
            $table->timestamps();
        });

        Schema::create('dim_team', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->unique();
            $table->string('name');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dim_department', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->unique();
            $table->string('name');
            $table->unsignedBigInteger('directorate_id')->nullable();
            $table->string('directorate_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dim_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_id')->unique();
            $table->string('name', 100);
            $table->string('channel_name')->nullable();
            $table->unsignedInteger('aht_goal')->default(300);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dim_shift', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id')->unique();
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('total_minutes');
            $table->unsignedSmallInteger('lunch_minutes')->default(45);
            $table->unsignedSmallInteger('break_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dim_skill', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('skill_id')->unique();
            $table->string('name');
            $table->string('code', 50);
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('CREATE OR REPLACE VIEW dim_date AS SELECT date, day, month, year, quarter, day_of_week, day_name, month_name, week_of_year, is_weekend, is_business_day, is_holiday, holiday_name FROM analytics_calendar_dimension');

        DB::statement('CREATE OR REPLACE VIEW dim_interval AS SELECT id AS interval_id, interval_key, start_time, end_time, interval_minutes, slot_number, label FROM analytics_time_interval_dimension');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS dim_interval');
        DB::statement('DROP VIEW IF EXISTS dim_date');
        Schema::dropIfExists('dim_skill');
        Schema::dropIfExists('dim_shift');
        Schema::dropIfExists('dim_queue');
        Schema::dropIfExists('dim_department');
        Schema::dropIfExists('dim_team');
        Schema::dropIfExists('dim_employee');
    }
};
