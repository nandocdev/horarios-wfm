<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_calendar_dimension', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedSmallInteger('day');
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('quarter');
            $table->unsignedSmallInteger('day_of_week')->comment('1=Lunes, 7=Domingo');
            $table->string('day_name', 10);
            $table->string('month_name', 10);
            $table->unsignedSmallInteger('week_of_year');
            $table->boolean('is_weekend');
            $table->boolean('is_business_day')->default(true);
            $table->boolean('is_holiday')->default(false);
            $table->string('holiday_name', 100)->nullable();
            $table->timestamps();

            $table->index('year');
            $table->index('month');
            $table->index('is_holiday');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_calendar_dimension');
    }
};
