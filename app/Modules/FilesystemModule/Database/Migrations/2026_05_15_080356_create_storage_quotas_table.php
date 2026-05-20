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
        Schema::create('storage_quotas', function (Blueprint $table) {
            $table->id();
            $table->string('target_type'); // 'user' o 'role'
            $table->unsignedBigInteger('target_id');
            $table->bigInteger('quota_limit'); // En bytes
            $table->timestamps();

            $table->unique(['target_type', 'target_id']);
        });

        // Insertar cuotas por defecto para roles existentes si es necesario
        // O simplemente dejar que el sistema use un default si no hay registro
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_quotas');
    }
};
