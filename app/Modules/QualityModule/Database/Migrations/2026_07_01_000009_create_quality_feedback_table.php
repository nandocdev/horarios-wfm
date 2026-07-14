<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_feedback', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('evaluation_id')->constrained('quality_evaluations')->cascadeOnDelete();
            $table->text('obsfeed');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_feedback');
    }
};
