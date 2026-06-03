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
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('knowledge_queues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->foreignId('category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, review, published, archived
            $table->integer('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at', 'expires_at']);
        });

        Schema::create('knowledge_article_queue', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('queue_id')->constrained('knowledge_queues')->cascadeOnDelete();
            $table->primary(['article_id', 'queue_id']);
        });

        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('knowledge_article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('knowledge_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('knowledge_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->integer('version');
            $table->longText('content');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_versions');
        Schema::dropIfExists('knowledge_article_tag');
        Schema::dropIfExists('knowledge_tags');
        Schema::dropIfExists('knowledge_article_queue');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_queues');
        Schema::dropIfExists('knowledge_categories');
    }
};
