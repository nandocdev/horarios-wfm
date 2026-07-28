<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_time_interval_dimension', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('interval_key', 5)->unique()->comment('Formato HH:MM, ej. 00:00, 00:15');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('interval_minutes')->default(15);
            $table->unsignedTinyInteger('slot_number')->unique()->comment('1-96 para intervalos de 15min');
            $table->string('label', 15)->comment('Ej. 00:00 - 00:15');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_time_interval_dimension');
    }
};
