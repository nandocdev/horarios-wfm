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
            $table->decimal('fcr_pct', 5, 2)->nullable()->after('quality_score')
                ->comment('First Call Resolution % - heurística por repetición phone+case en 7d');
            $table->decimal('transfer_rate_pct', 5, 2)->nullable()->after('fcr_pct')
                ->comment('Transfer Rate % - requiere dato de transferencias');
        });
    }

    public function down(): void
    {
        Schema::table('daily_kpis', function (Blueprint $table) {
            $table->dropColumn(['fcr_pct', 'transfer_rate_pct']);
        });
    }
};
