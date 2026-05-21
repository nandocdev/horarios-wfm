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
        Schema::table('agent_daily_metrics', function (Blueprint $table) {
            $table->decimal('availability_pct', 10, 2)->change();
            $table->decimal('efficiency_pct', 10, 2)->change();
            $table->decimal('pwi_pct', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_daily_metrics', function (Blueprint $table) {
            $table->decimal('availability_pct', 5, 2)->change();
            $table->decimal('efficiency_pct', 5, 2)->change();
            $table->decimal('pwi_pct', 5, 2)->change();
        });
    }
};
