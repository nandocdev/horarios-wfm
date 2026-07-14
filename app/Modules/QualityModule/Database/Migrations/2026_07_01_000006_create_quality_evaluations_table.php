<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_evaluations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('queue_id')->constrained('quality_queues');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('evaluator_id')->constrained('users');
            $table->unsignedBigInteger('clip_id')->nullable()->comment('FK a processed_clips (videoparser)');
            $table->date('dtcall')->nullable();
            $table->time('tmcall')->nullable();
            $table->date('dteval');
            $table->time('tmeval');
            $table->unsignedSmallInteger('score')->nullable();
            $table->text('callobs')->nullable();
            $table->boolean('has_redflag')->default(false);
            $table->string('status', 20)->default('activa');
            $table->softDeletes();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('evaluator_id');
            $table->index('dteval');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_evaluations');
    }
};
