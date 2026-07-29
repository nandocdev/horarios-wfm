<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_kpis', function (Blueprint $table) {
            $table->decimal('productivity', 5, 2)->nullable()->after('utilization')
                ->comment('(talk+hold+wrap)/(login_time)*100');
            $table->decimal('conformance', 5, 2)->nullable()->after('productivity')
                ->comment('(actual_worked_minutes/scheduled_minutes)*100');
            $table->decimal('acw_seconds', 10, 2)->nullable()->after('aht_seconds')
                ->comment('After Call Work promedio en segundos');
        });
    }

    public function down(): void
    {
        Schema::table('daily_kpis', function (Blueprint $table) {
            $table->dropColumn(['productivity', 'conformance', 'acw_seconds']);
        });
    }
};
