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
        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentioned_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentioner_user_id')->constrained('users')->onDelete('cascade');
            $table->string('mentionable_type');
            $table->string('mentionable_id');
            $table->index(['mentionable_type', 'mentionable_id']);
            $table->string('context')->nullable(); // Texto donde se hizo la mención
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Índices para performance
            $table->index(['mentioned_user_id', 'is_read']);
            $table->index(['mentioner_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
