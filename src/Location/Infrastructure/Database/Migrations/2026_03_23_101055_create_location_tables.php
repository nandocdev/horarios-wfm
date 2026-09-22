<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('province_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->timestamps();
                $table->unique(['province_id', 'name']);
            });
        }

        if (! Schema::hasTable('townships')) {
            Schema::create('townships', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('district_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->timestamps();
                $table->unique(['district_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('townships');
        Schema::dropIfExists('districts');
    }
};
