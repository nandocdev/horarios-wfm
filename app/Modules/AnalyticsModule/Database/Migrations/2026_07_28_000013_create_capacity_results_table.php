<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('capacity_plan_id')->constrained('capacity_plans');
            $table->string('queue_id', 100);
            $table->unsignedSmallInteger('total_intervals')->default(0);
            $table->unsignedSmallInteger('intervals_with_gap')->default(0);
            $table->unsignedSmallInteger('intervals_with_skill_gap')->default(0);
            $table->decimal('max_gap', 10, 2)->default(0);
            $table->decimal('avg_coverage', 5, 2)->default(0);
            $table->decimal('total_staff_required', 10, 2)->default(0);
            $table->decimal('total_staff_available', 10, 2)->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['capacity_plan_id', 'queue_id']);
            $table->index('queue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_results');
    }
};
