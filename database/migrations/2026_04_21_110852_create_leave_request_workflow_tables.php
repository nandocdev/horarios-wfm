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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // quarterly, compensatory, etc.
            $table->dateTimeTz('start_time');
            $table->dateTimeTz('end_time');
            $table->integer('minutes'); // Duración calculada
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestampsTz();

            $table->index(['employee_id', 'status', 'type']);
            $table->index('start_time');
        });

        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('employees')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->integer('step_order')->default(1);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_request_approvals');
        Schema::dropIfExists('leave_requests');
    }
};
