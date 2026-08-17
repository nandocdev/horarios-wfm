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
        // La puerta/consultorio se asocia ahora a cada servicio dependiente del piso,
        // no a la unidad (piso) directamente.
        Schema::table('directory_units', function (Blueprint $table) {
            $table->dropColumn('door_id');
        });

        Schema::table('directory_services', function (Blueprint $table) {
            $table->string('door_id')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_services', function (Blueprint $table) {
            $table->dropColumn('door_id');
        });

        Schema::table('directory_units', function (Blueprint $table) {
            $table->string('door_id')->nullable();
        });
    }
};
