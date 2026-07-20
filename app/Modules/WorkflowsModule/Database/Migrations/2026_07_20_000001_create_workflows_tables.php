<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('requestable');
            $table->foreignId('requester_id')->constrained('employees');
            $table->string('type'); // leave, swap, exception
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->jsonb('data')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('employees');
            $table->unsignedTinyInteger('step_order');
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['workflow_request_id', 'step_order']);
        });

        Schema::create('workflow_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_approver_id')->constrained('employees');
            $table->foreignId('delegate_id')->constrained('employees');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['original_approver_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_delegations');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_requests');
    }
};
