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
        // 1. Directorates
        Schema::create('directorates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Disability Types
        Schema::create('disability_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Disease Types
        Schema::create('disease_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Employment Statuses
        Schema::create('employment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('employment_statuses')->onDelete('set null');
            $table->timestamps();
            $table->index(['parent_id'], 'employment_statuses_parent_idx');
        });

        // 5. Incident Types (WFM Core foundation)
        Schema::create('incident_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('color', ['red', 'blue', 'white'])->default('blue');
            $table->boolean('requires_justification')->default(false);
            $table->boolean('affects_availability')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Provinces
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 7. Teams
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('cisco_team_id')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('incident_types');
        Schema::dropIfExists('employment_statuses');
        Schema::dropIfExists('disease_types');
        Schema::dropIfExists('disability_types');
        Schema::dropIfExists('directorates');
    }
};
