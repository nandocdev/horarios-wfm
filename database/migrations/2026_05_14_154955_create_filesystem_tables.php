<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Folders
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#3b82f6'); // FluxUI primary blue
            $table->timestampsTz();

            $table->index(['user_id', 'parent_id']);
        });

        // 2. Files
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->bigInteger('size')->comment('Size in bytes');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->boolean('is_public')->default(false);
            $table->timestampsTz();

            $table->index(['user_id', 'folder_id']);
        });

        // 3. File Sharing (Permissions)
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            // Can share a file OR a folder
            $table->foreignId('file_id')->nullable()->constrained('files')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Target user
            $table->foreignId('shared_by_id')->constrained('users')->cascadeOnDelete();
            
            $table->enum('access_level', ['view', 'edit', 'admin'])->default('view');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();

            $table->unique(['file_id', 'user_id'], 'idx_file_user_share');
            $table->unique(['folder_id', 'user_id'], 'idx_folder_user_share');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_shares');
        Schema::dropIfExists('files');
        Schema::dropIfExists('folders');
    }
};
