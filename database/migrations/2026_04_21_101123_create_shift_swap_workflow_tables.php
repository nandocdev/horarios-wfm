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
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('employees')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'approved', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->jsonb('requester_assignment_snapshot')->nullable();
            $table->jsonb('recipient_assignment_snapshot')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestampsTz();

            // Index para búsquedas rápidas
            $table->index(['requester_id', 'status']);
            $table->index(['recipient_id', 'status']);
            $table->index('start_date');
        });

        Schema::create('shift_swap_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_swap_request_id')->constrained('shift_swap_requests')->onDelete('cascade');
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
        Schema::dropIfExists('shift_swap_approvals');
        Schema::dropIfExists('shift_swap_requests');
    }
};
