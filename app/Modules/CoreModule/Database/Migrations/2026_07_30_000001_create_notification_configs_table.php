<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_type')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->jsonb('channels');
            $table->string('recipient_type')->nullable();
            $table->jsonb('recipient_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_configs');
    }
};
