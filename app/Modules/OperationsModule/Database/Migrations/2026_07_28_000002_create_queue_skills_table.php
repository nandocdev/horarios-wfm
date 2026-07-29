<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('call_queues');
            $table->foreignId('skill_id')->constrained('skills');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->unsignedSmallInteger('minimum_level')->default(1)->comment('Nivel mínimo requerido en esta cola');
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['queue_id', 'skill_id']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_skills');
    }
};
