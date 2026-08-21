<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organizational_units')->onDelete('set null');
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('acronym', 20)->nullable();
            $table->string('level', 50);  // direction, management, coordination, supervision, operational
            $table->text('description')->nullable();
            $table->foreignId('head_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('level');
            $table->index('is_active');
            $table->index('head_employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
};