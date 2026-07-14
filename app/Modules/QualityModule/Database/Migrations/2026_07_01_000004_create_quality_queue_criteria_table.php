<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_queue_criteria', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('queue_id')->constrained('quality_queues')->cascadeOnDelete();
            $table->foreignUlid('criteria_version_id')->constrained('quality_criteria_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['queue_id', 'criteria_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_queue_criteria');
    }
};
