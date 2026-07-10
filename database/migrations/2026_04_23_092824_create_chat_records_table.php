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
        Schema::create('chat_records', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id')->unique();
            $table->string('agent_login_id')->index();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->timestamp('start_time')->index();
            $table->timestamp('end_time')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->unsignedInteger('total_duration')->default(0);
            $table->unsignedInteger('talk_time')->default(0);

            $table->string('author_identifier')->nullable(); // autor_de_conversación
            $table->string('destination_identifier')->nullable(); // destino_de_conversación

            $table->string('chat_type')->nullable(); // One-to-One
            $table->string('chat_source')->nullable(); // WhatsApp, WebChat, etc.
            $table->string('chat_rating')->nullable();

            $table->string('raw_agent_name')->nullable();
            $table->timestamps();

            $table->index(['agent_login_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_records');
    }
};
