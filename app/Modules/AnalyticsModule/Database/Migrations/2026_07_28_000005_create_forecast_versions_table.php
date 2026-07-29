<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('forecast_group_id')->constrained('forecast_groups');
            $table->unsignedInteger('version_number');
            $table->string('name');
            $table->string('status', 50)->default('draft')->comment('draft, published, archived');
            $table->foreignId('generated_by')->nullable()->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['forecast_group_id', 'version_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_versions');
    }
};
