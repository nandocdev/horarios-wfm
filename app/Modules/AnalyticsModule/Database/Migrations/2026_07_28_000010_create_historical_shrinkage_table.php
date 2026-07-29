<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_shrinkage', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignUlid('shrinkage_category_id')->constrained('shrinkage_categories');
            $table->date('date');
            $table->timestamp('interval_start')->nullable();
            $table->timestamp('interval_end')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('source_type', 50)->comment('schedule_exception, leave_request, intraday_activity, manual');
            $table->string('source_id')->nullable()->comment('ID del registro origen');
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('date');
            $table->index('shrinkage_category_id');
            $table->index(['date', 'shrinkage_category_id']);
            $table->unique(['employee_id', 'interval_start', 'source_type', 'source_id'], 'historical_shrinkage_unique_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_shrinkage');
    }
};
