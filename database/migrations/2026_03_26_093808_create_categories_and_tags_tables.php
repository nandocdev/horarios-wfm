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
        // Categorías para clasificación de contenido
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Hex color
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Tags para etiquetado flexible
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#6B7280'); // Hex color
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // Relación polimórfica: categorías pueden aplicarse a news, polls, shoutouts
        Schema::create('categorizables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('categorizable_type');
            $table->string('categorizable_id');
            $table->index(['categorizable_type', 'categorizable_id']);
            $table->timestamps();

            $table->unique(['category_id', 'categorizable_type', 'categorizable_id'], 'categorizables_unique');
            $table->index(['categorizable_type', 'categorizable_id'], 'categorizables_morph_index');
        });

        // Relación polimórfica: tags pueden aplicarse a news, polls, shoutouts
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->string('taggable_type');
            $table->string('taggable_id');
            $table->index(['taggable_type', 'taggable_id']);
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_type', 'taggable_id'], 'taggables_unique');
            $table->index(['taggable_type', 'taggable_id'], 'taggables_morph_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('categorizables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
