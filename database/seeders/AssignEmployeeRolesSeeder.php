<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignEmployeeRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('data/employees.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("Archivo CSV de empleados no encontrado: {$csvPath}");

            return;
        }

        $employees = $this->readCsv($csvPath);

        // Mapeo detallado de Position ID a Role Name
        $roleMapping = [
            1 => 'operator',    // Operador I
            2 => 'operator',    // Operador II
            3 => 'wfm',  // Analista Control y Monitoreo
            4 => 'chief',       // Jefe de Sección III
            5 => 'coordinator', // Coordinador de Asistencia
            6 => 'director',    // Director Nacional
            7 => 'chief',       // Jefe Control y Monitoreo
            8 => 'wfm',         // Administrador Soluciones Analíticas (WFM)
            9 => 'chief',       // Jefe Asist. Serv. Pub.
            10 => 'operator',    // Mensajero II
            11 => 'operator',    // Analista Calc. Pens.
            12 => 'wfm', // Coordinador Rendimiento
            13 => 'operator',    // Investigador
        ];

        DB::transaction(function () use ($employees, $roleMapping) {
            foreach ($employees as $row) {
                $user = User::query()->where('email', $row['email'])->first();

                if ($user !== null) {
                    $roleName = $roleMapping[$row['position_id']] ?? 'operator';

                    // Asignar rol. Nota: syncRoles reemplaza roles previos.
                    // Si el usuario ya es admin (asignado manualmente en DatabaseSeeder), se mantendrá en DatabaseSeeder.
                    $user->syncRoles([$roleName]);
                }
            }
        });

        $this->command->info('Roles institucionales asignados a 108 usuarios exitosamente.');
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
