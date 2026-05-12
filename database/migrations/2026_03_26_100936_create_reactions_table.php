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
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shoutout_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['like', 'love', 'celebrate', 'support', 'insightful']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unicidad: un usuario solo puede tener una reacción por shoutout
            $table->unique(['shoutout_id', 'user_id']);
            // Índices para performance
            $table->index(['shoutout_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
