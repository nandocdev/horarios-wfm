<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operational_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->string('category')->default('threshold')->after('description');
            $table->timestamps();
        });

        $goals = [
            [
                'key' => 'goal_adherence',
                'value' => '90',
                'description' => 'Meta de porcentaje de adherencia esperada',
                'category' => 'kpi_goal',
            ],
            [
                'key' => 'goal_productivity',
                'value' => '85',
                'description' => 'Meta de porcentaje de productividad esperada',
                'category' => 'kpi_goal',
            ],
            [
                'key' => 'goal_utilization',
                'value' => '80',
                'description' => 'Meta de porcentaje de utilización esperada',
                'category' => 'kpi_goal',
            ],
            [
                'key' => 'goal_service_level',
                'value' => '80',
                'description' => 'Meta de nivel de servicio (Service Level)',
                'category' => 'kpi_goal',
            ],
            [
                'key' => 'goal_coverage',
                'value' => '80',
                'description' => 'Meta de cobertura operativa (%) para el gráfico de cobertura del día',
                'category' => 'kpi_goal',
            ],
            [
                'key' => 'goal_absence_rate',
                'value' => '5',
                'description' => 'Tasa máxima aceptable de ausentismo (%)',
                'category' => 'kpi_goal',
            ],
        ];

        foreach ($goals as $goal) {
            DB::table('operational_settings')->updateOrInsert(
                ['key' => $goal['key']],
                $goal
            );
        }
    }

    public function down(): void
    {
        DB::table('operational_settings')
            ->whereIn('key', [
                'goal_adherence', 'goal_productivity', 'goal_utilization',
                'goal_service_level', 'goal_coverage', 'goal_absence_rate',
            ])
            ->delete();

        Schema::table('operational_settings', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::dropIfExists('operational_settings');
    }
};
