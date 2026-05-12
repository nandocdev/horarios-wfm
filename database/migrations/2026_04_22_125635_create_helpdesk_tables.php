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
        Schema::create('helpdesk_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->integer('sla_hours')->default(48); // SLA sugerido para la categoría
            $table->string('color', 20)->default('zinc');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description');
            $table->foreignId('category_id')->constrained('helpdesk_categories');
            $table->foreignId('creator_id')->constrained('employees'); // Operador que abre
            $table->foreignId('assigned_agent_id')->nullable()->constrained('employees'); // Soporte asignado

            // Estados: new, open, in_progress, on_hold, resolved, closed
            $table->enum('status', ['new', 'open', 'in_progress', 'on_hold', 'resolved', 'closed'])->default('new');
            // Prioridad: low, medium, high, urgent
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();

            // Índices para búsquedas rápidas
            $table->index(['status', 'priority']);
            $table->index('creator_id');
            $table->index('assigned_agent_id');
        });

        Schema::create('helpdesk_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('employees'); // Quien escribe
            $table->text('content');
            $table->boolean('is_internal')->default(false); // Para notas privadas entre soporte
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_comments');
        Schema::dropIfExists('helpdesk_tickets');
        Schema::dropIfExists('helpdesk_categories');
    }
};
