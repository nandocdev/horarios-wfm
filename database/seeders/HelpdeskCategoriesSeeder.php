<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use Illuminate\Database\Seeder;

class HelpdeskCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'xHIS (Historia Clínica)',
                'description' => 'Errores en consulta o registro de historia clínica, datos inconsistentes o fallos en integración.',
                'sla_hours' => 8,
                'color' => 'red',
            ],
            [
                'name' => 'eSIAP (Sistema Administrativo)',
                'description' => 'Problemas en procesos administrativos, registros, validaciones o fallos del sistema eSIAP.',
                'sla_hours' => 12,
                'color' => 'amber',
            ],
            [
                'name' => 'Validación de Derecho',
                'description' => 'Errores al validar cobertura del asegurado, inconsistencias de estado o fallos de consulta.',
                'sla_hours' => 4,
                'color' => 'red',
            ],
            [
                'name' => 'Citas Médicas',
                'description' => 'Fallas en agendamiento, reprogramaciones, cancelaciones o disponibilidad de citas.',
                'sla_hours' => 6,
                'color' => 'blue',
            ],
            [
                'name' => 'Laboratorio',
                'description' => 'Problemas en solicitudes, resultados o integración con sistemas de laboratorio.',
                'sla_hours' => 12,
                'color' => 'violet',
            ],
            [
                'name' => 'Farmacia',
                'description' => 'Errores en dispensación, recetas, inventario o validación de medicamentos.',
                'sla_hours' => 12,
                'color' => 'green',
            ],
            [
                'name' => 'Audio / Telefonía',
                'description' => 'Problemas de audio, diademas, llamadas, IVR o calidad de voz en el call center.',
                'sla_hours' => 4,
                'color' => 'red',
            ],
            [
                'name' => 'Equipos (Hardware)',
                'description' => 'Fallas físicas en equipos: PC, monitores, periféricos o estaciones de trabajo.',
                'sla_hours' => 24,
                'color' => 'zinc',
            ],
            [
                'name' => 'Otros',
                'description' => 'Incidencias no clasificadas o fuera de las categorías definidas.',
                'sla_hours' => 48,
                'color' => 'gray',
            ],
        ];

        foreach ($categories as $cat) {
            HelpdeskCategory::updateOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
