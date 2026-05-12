<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recrea la tabla de notificaciones para ser 100% compatible con el sistema nativo de Laravel.
     * 
     * EL ERROR: El driver 'database' de Laravel intenta insertar UUIDs en una columna BIGINT,
     * y además omite columnas obligatorias personalizadas (user_id, title, message).
     */
    public function up(): void
    {
        // Eliminamos la tabla para reconstruirla con el esquema correcto
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            // Estándar Laravel
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            
            // Columnas personalizadas del proyecto (ahora opcionales para evitar crashes)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();

            // Índices adicionales
            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al esquema anterior si es necesario
        Schema::dropIfExists('notifications');
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('notifiable_type');
            $table->string('notifiable_id');
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
