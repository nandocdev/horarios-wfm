<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('group_type', 50)->comment('queue, skill, channel');
            $table->string('reference_id')->nullable()->comment('ID de la entidad referenciada (cola, skill, etc.)');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('group_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_groups');
    }
};
