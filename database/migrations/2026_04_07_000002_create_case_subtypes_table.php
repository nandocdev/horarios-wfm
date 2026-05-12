<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_subtypes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('queue_id')->constrained('call_queues')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['queue_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_subtypes');
    }
};
