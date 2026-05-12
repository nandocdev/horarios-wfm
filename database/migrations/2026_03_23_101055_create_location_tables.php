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
        // 1. Districts (F: provinces)
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->unique(['province_id', 'name']);
        });

        // 2. Townships (F: districts)
        Schema::create('townships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->unique(['district_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('townships');
        Schema::dropIfExists('districts');
    }
};
