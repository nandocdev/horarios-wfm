<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedTeams();
        });
    }

    private function seedTeams(): void
    {
        $teams = [
            [
                'id' => 1,
                'name' => 'Direccion',
                'description' => 'Direccion Nacional de Asistencia de los Servicios al Asegurado',
                'supervisor_id' => 1, // Asumiendo que el empleado con ID 1 existe
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Servicios al Asegurado',
                'description' => 'Departamento de Asistencia de los Servicios al Asegurado',
                'manager_email' => 'khubner@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Servicios para la Salud',
                'description' => 'Departamento de Servicios para la Salud',
                'manager_email' => 'ericgonzalezv@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Control y Monitoreo e Ingeniería',
                'description' => 'Equipo de Control y Monitoreo e Ingeniería',
                'manager_email' => 'ralgandona@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
                'cisco_team_id' => 11,
            ],
            [
                'id' => 5,
                'name' => 'Coordinación Lorena Cortés',
                'description' => 'Equipo de coordinación 1',
                'manager_email' => 'lcortez@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 5 existe
                'is_active' => true,
                'cisco_team_id' => 9,
            ],
            [
                'id' => 6,
                'name' => 'Coordinación Daniel Fuentes',
                'description' => 'Equipo de coordinación 2',
                'manager_email' => 'dafuentes@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 6 existe
                'is_active' => true,
                'cisco_team_id' => 7,
            ],
            [
                'id' => 7,
                'name' => 'Coordinación Glenis Guerra',
                'description' => 'Equipo de coordinación 3',
                'manager_email' => 'glguerra@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 7 existe
                'is_active' => true,
                'cisco_team_id' => 6,
            ],
            [
                'id' => 8,
                'name' => 'Coordinación Dixiana Guerrero',
                'description' => 'Equipo de coordinación 4',
                'manager_email' => 'dixguerrero@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 8 existe
                'is_active' => true,
                'cisco_team_id' => 20,
            ],
            [
                'id' => 9,
                'name' => 'Coordinación Lorena Guevara',
                'description' => 'Equipo de coordinación 5',
                'manager_email' => 'loguevara@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 9 existe
                'is_active' => true,
                'cisco_team_id' => 21,
            ],
            [
                'id' => 10,
                'name' => 'Coordinación Valerie Quiros',
                'description' => 'Equipo de coordinación 6',
                'manager_email' => 'vquiros@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 10 existe
                'is_active' => true,
                'cisco_team_id' => 4,
            ],
            [
                'id' => 11,
                'name' => 'Coordinación Yaremi Rahman',
                'description' => 'Equipo de coordinación 7',
                'manager_email' => 'yrahman@css.gob.pa',
                'supervisor_id' => null, // Asumiendo que el empleado con ID 11 existe
                'is_active' => true,
                'cisco_team_id' => 22,
            ],
            [
                'id' => 12,
                'name' => 'Coordinación Daira Reina',
                'description' => 'Equipo de coordinación 8',
                'manager_email' => 'dreina@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
                'cisco_team_id' => 3,
            ],
            [
                'id' => 13,
                'name' => 'Coordinación Antonia Tejada',
                'description' => 'Equipo de coordinación 9',
                'manager_email' => 'antejada@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
                'cisco_team_id' => 8,
            ],
            [
                'id' => 14,
                'name' => 'Coordinación Victor Tejada',
                'description' => 'Equipo de coordinación 10',
                'manager_email' => 'vtejada@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
                'cisco_team_id' => 5,
            ],
            [
                'id' => 15,
                'name' => 'Recursos Humanos',
                'description' => 'Equipo de coordinación 11',
                'manager_email' => 'mcastillo@css.gob.pa',
                'supervisor_id' => null, // buscamos el correo en la tabla employees para obtener el id
                'is_active' => true,
            ],
        ];

        foreach ($teams as $team) {
            if (isset($team['manager_email'])) {
                $team['supervisor_id'] = DB::table('employees')
                    ->where('email', $team['manager_email'])
                    ->value('id');
            }

            DB::table('teams')->updateOrInsert(
                ['id' => $team['id']],
                [
                    'name' => $team['name'],
                    'description' => $team['description'],
                    'supervisor_id' => $team['supervisor_id'] ?? null,
                    'is_active' => $team['is_active'],
                    'cisco_team_id' => $team['cisco_team_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Equipos sembrados exitosamente.');
    }
}
