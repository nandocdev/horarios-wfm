<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_daily_metrics', function (Blueprint $table) {
            $table->integer('handled_calls')->default(0)->after('calls_total');
            $table->integer('work_seconds')->default(0)->after('talk_seconds');
            $table->integer('hold_seconds')->default(0)->after('work_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('agent_daily_metrics', function (Blueprint $table) {
            $table->dropColumn(['handled_calls', 'work_seconds', 'hold_seconds']);
        });
    }
};
