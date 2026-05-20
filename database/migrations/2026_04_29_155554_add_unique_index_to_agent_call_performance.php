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
        Schema::table('agent_call_performance', function (Blueprint $table) {
            $table->unique(['agent_login_id', 'start_time'], 'agent_performance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_call_performance', function (Blueprint $table) {
            $table->dropUnique('agent_performance_unique');
        });
    }
};
