<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insertar valores iniciales
        DB::table('app_settings')->insert([
            [
                'key' => 'maintenance_mode',
                'value' => json_encode(['enabled' => false, 'message' => 'El sistema se encuentra en mantenimiento programado.']),
                'description' => 'Estado del modo mantenimiento',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
