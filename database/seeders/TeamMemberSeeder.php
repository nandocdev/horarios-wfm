<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Siembra los miembros de equipo basándose en la jerarquía de 'parent_id'
 * y los supervisores definidos en los equipos.
 */
class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::all();

        if ($teams->isEmpty()) {
            $this->command->warn('No hay equipos creados. Corre TeamManagerSeeder primero.');

            return;
        }

        DB::transaction(function () use ($teams) {
            foreach ($teams as $team) {
                if (! $team->supervisor_id) {
                    continue;
                }

                $count = 0;

                // 1. Añadir al propio supervisor al equipo
                $this->addMember($team->id, $team->supervisor_id);
                $count++;

                // 2. Añadir sub-jerarquía directa (Subordinados del supervisor)
                $subordinates = Employee::where('parent_id', $team->supervisor_id)->get();
                foreach ($subordinates as $subordinate) {
                    $this->addMember($team->id, $subordinate->id);
                    $count++;

                    // 3. Opcional: Sub-subordinados (para completar la profundidad si aplica)
                    $operatives = Employee::where('parent_id', $subordinate->id)->get();
                    foreach ($operatives as $operative) {
                        $this->addMember($team->id, $operative->id);
                        $count++;
                    }
                }

                $this->command->info("Equipo {$team->name}: {$count} miembros asignados.");
            }
        });

        // Sincronizar columna denormalizada team_id en employees para rapidez en queries
        $this->syncEmployeeTeamIds();

        $this->command->info('Jerarquía de equipos sembrada exitosamente.');
    }

    private function addMember(int $teamId, int $employeeId): void
    {
        DB::table('team_members')->updateOrInsert(
            ['team_id' => $teamId, 'employee_id' => $employeeId],
            [
                'joined_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Actualiza la columna team_id en la tabla employees basada en la relación activa
     * de team_members, facilitando el filtrado en la grilla WFM.
     */
    private function syncEmployeeTeamIds(): void
    {
        $memberships = DB::table('team_members')
            ->where('is_active', true)
            ->get();

        foreach ($memberships as $membership) {
            DB::table('employees')
                ->where('id', $membership->employee_id)
                ->update(['team_id' => $membership->team_id]);
        }
    }
}
