<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\OperationsModule\Models\IncidentType;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'LATE',
                'name' => 'Tardanza',
                'color' => '#f59e0b', // Amber
                'requires_justification' => true,
                'affects_availability' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ABSENT',
                'name' => 'Ausencia',
                'color' => '#ef4444', // Red
                'requires_justification' => true,
                'affects_availability' => true,
                'is_active' => true,
            ],
            [
                'code' => 'EARLY_DEPARTURE',
                'name' => 'Salida Temprana',
                'color' => '#8b5cf6', // Violet
                'requires_justification' => true,
                'affects_availability' => true,
                'is_active' => true,
            ],
            [
                'code' => 'UNSCHEDULED_LOGOUT',
                'name' => 'Desconexión no programada',
                'color' => '#6b7280', // Gray
                'requires_justification' => true,
                'affects_availability' => true,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            IncidentType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
