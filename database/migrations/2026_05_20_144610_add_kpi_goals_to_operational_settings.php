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
        if (! Schema::hasColumn('operational_settings', 'category')) {
            Schema::table('operational_settings', function (Blueprint $table) {
                $table->string('category')->default('threshold')->after('description');
            });
        }

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
        ];

        foreach ($goals as $goal) {
            DB::table('operational_settings')->updateOrInsert(
                ['key' => $goal['key']],
                $goal
            );
        }

        // Update existing ones to 'threshold' category explicitly
        DB::table('operational_settings')
            ->whereNull('category')
            ->orWhere('category', 'threshold')
            ->update(['category' => 'threshold']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_settings', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        DB::table('operational_settings')
            ->whereIn('key', ['goal_adherence', 'goal_productivity', 'goal_utilization', 'goal_service_level'])
            ->delete();
    }
};
