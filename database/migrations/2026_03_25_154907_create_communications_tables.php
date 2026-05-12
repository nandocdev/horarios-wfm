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
        // Noticias
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->foreignId('author_id')->constrained('users');
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->jsonb('version_history')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('archive_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('approved_by');
            $table->index(['status', 'scheduled_at'], 'news_status_scheduled_at_index');
            $table->index(['status', 'archive_at'], 'news_status_archive_at_index');
        });

        // Shoutouts (Reconocimientos rápidos)
        Schema::create('shoutouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('message');
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->jsonb('version_history')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('archive_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('approved_by');
            $table->index(['status', 'scheduled_at'], 'shoutouts_status_scheduled_at_index');
            $table->index(['status', 'archive_at'], 'shoutouts_status_archive_at_index');
        });

        // Encuestas (Polls)
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->jsonb('options'); // [{label, value}]
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->jsonb('version_history')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('archive_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('approved_by');
            $table->index(['status', 'scheduled_at'], 'polls_status_scheduled_at_index');
            $table->index(['status', 'archive_at'], 'polls_status_archive_at_index');
            $table->index('reminder_sent_at', 'polls_reminder_sent_at_index');
        });

        // Respuestas de Encuestas
        Schema::create('poll_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('answer');
            $table->timestamps();
            $table->unique(['poll_id', 'user_id']); // Solo un voto por usuario
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_responses');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('shoutouts');
        Schema::dropIfExists('news');
    }
};
