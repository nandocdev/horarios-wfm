<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('finesse_queue_id')->nullable()->unique();
            $table->string('name')->unique();
            $table->ulid('channel_id')->nullable();
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');
            $table->text('description')->nullable();
            $table->integer('aht_goal')->nullable()->default(300)->comment('Goal for Average Handle Time in seconds');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_queues');
    }
};
