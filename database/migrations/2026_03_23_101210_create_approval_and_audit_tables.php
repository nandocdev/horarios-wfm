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

        // 3. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->string('entity_id');
            $table->string('action'); // created, updated, deleted, login, etc.
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

    }
};
