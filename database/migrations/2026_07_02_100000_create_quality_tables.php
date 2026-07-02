<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quality_evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('quality_evaluation_forms')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_score');
            $table->decimal('weight', 5, 2);
            $table->boolean('is_fatal_error')->default(false);
            $table->timestamps();
        });

        Schema::create('quality_agent_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('quality_evaluation_forms')->cascadeOnDelete();
            $table->jsonb('scores');
            $table->decimal('total_score', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('draft');
            $table->timestampTz('evaluated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quality_dispute_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('quality_agent_evaluations')->cascadeOnDelete();
            $table->foreignId('raised_by_agent_id')->constrained('employees')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('open');
            $table->text('resolution_comment')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_dispute_requests');
        Schema::dropIfExists('quality_agent_evaluations');
        Schema::dropIfExists('quality_evaluation_criteria');
        Schema::dropIfExists('quality_evaluation_forms');
    }
};
