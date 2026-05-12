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
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('absence_reason_code_id')->constrained('absence_reason_codes')->cascadeOnDelete();

            $table->dateTimeTz('start_at');
            $table->dateTimeTz('end_at');
            $table->boolean('is_full_day')->default(true);

            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();

            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['employee_id', 'start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
    }
};
