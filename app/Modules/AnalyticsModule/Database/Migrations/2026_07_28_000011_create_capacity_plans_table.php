<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('draft')->comment('draft, completed, archived');
            $table->date('plan_date');
            $table->foreignId('generated_by')->nullable()->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->string('forecast_version_id')->nullable()->comment('ID del forecast usado como base');
            $table->decimal('shrinkage_rate', 5, 2)->default(0)->comment('Tasa de shrinkage aplicada');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('plan_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_plans');
    }
};
