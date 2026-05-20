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
        Schema::create('storage_quotas', function (Blueprint $blade) {
            $blade->id();
            $blade->string('target_type'); // 'user' o 'role'
            $blade->unsignedBigInteger('target_id');
            $blade->unsignedBigInteger('quota_limit'); // En bytes
            $blade->timestamps();

            $blade->unique(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_quotas');
    }
};
