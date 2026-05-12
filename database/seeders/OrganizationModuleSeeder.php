<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedDepartments();
            $this->seedPositions();
        });
    }

    private function seedDepartments(): void
    {
        $csvPath = database_path('data/departments.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("Archivo CSV de departamentos no encontrado: {$csvPath}");

            return;
        }

        // Crear una Dirección por defecto (id=1) para asociar los departamentos
        DB::table('directorates')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Dirección General de CCS',
                'description' => 'Fomento de la organización institucional.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $departments = $this->readCsv($csvPath);

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(
                ['id' => $dept['id']],
                [
                    'directorate_id' => 1,
                    'name' => $dept['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Direcciones y Departamentos sembrados exitosamente.');
    }

    private function seedPositions(): void
    {
        $csvPath = database_path('data/positions.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("Archivo CSV de cargos no encontrado: {$csvPath}");

            return;
        }

        $positions = $this->readCsv($csvPath);

        foreach ($positions as $pos) {
            DB::table('positions')->updateOrInsert(
                ['id' => $pos['id']],
                [
                    'department_id' => 1,
                    'name' => $pos['name'],
                    'position_code' => 'P'.str_pad((string) $pos['id'], 5, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Cargos sembrados exitosamente.');
    }

    private function readCsv(string $filePath): array
    {
        $data = [];
        $header = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    if (count($header) === count($row)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
        }

        return $data;
    }
}
