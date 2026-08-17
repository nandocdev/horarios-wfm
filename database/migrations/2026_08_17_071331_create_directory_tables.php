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
        Schema::create('directory_buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('director_name');
            $table->string('subdirector_name')->nullable();
            $table->string('administrator_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('directory_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('directory_buildings')->cascadeOnDelete();
            $table->string('sector')->nullable();
            $table->string('level')->nullable();
            $table->string('door_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['building_id', 'is_active']);
        });

        Schema::create('directory_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('directory_units')->cascadeOnDelete();
            $table->string('name');
            $table->string('attention_hours');
            $table->string('results_hours')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'name']);
        });

        Schema::create('directory_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('directory_units')->cascadeOnDelete();
            $table->string('role');
            $table->string('extension');
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directory_contacts');
        Schema::dropIfExists('directory_services');
        Schema::dropIfExists('directory_units');
        Schema::dropIfExists('directory_buildings');
    }
};
