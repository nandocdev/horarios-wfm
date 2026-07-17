<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('operational_settings')->updateOrInsert(
            ['key' => 'goal_coverage'],
            [
                'value' => '80',
                'description' => 'Meta de cobertura operativa (%) para el gráfico de cobertura del día',
                'category' => 'kpi_goal',
            ]
        );

        DB::table('operational_settings')->updateOrInsert(
            ['key' => 'goal_absence_rate'],
            [
                'value' => '5',
                'description' => 'Tasa máxima aceptable de ausentismo (%)',
                'category' => 'kpi_goal',
            ]
        );
    }

    public function down(): void
    {
        DB::table('operational_settings')
            ->whereIn('key', ['goal_coverage', 'goal_absence_rate'])
            ->delete();
    }
};
