<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncEmployeeTeamsWithCiscoAction
{
    public function __construct(
        protected CiscoFinesseClient $finesseService
    ) {}

    /**
     * Sincroniza la relación de empleados y equipos desde Cisco Finesse.
     *
     * @return array Resumen de la sincronización.
     */
    public function execute(): array
    {
        $ciscoUsers = $this->finesseService->getUsers();
        $results = [
            'total' => count($ciscoUsers),
            'synced' => 0,
            'errors' => 0,
            'transfers' => 0,
        ];

        foreach ($ciscoUsers as $userData) {
            try {
                DB::transaction(function () use ($userData, &$results) {
                    // 1. Buscar empleado por loginId (cisco_username o username)
                    $employee = Employee::where('cisco_username', $userData['loginId'])
                        ->orWhere('username', $userData['loginId'])
                        ->first();

                    if (! $employee) {
                        return; // Empleado no encontrado localmente
                    }

                    // 2. Buscar equipo por cisco_team_id
                    $team = Team::where('cisco_team_id', $userData['teamId'])->first();

                    if (! $team) {
                        Log::warning("SyncEmployeeTeams: Team not found for Cisco ID {$userData['teamId']}");

                        return;
                    }

                    // 3. Verificar si el equipo ha cambiado
                    if ($employee->team_id !== $team->id) {
                        // Inactivar membresía actual
                        TeamMember::where('employee_id', $employee->id)
                            ->where('is_active', true)
                            ->update([
                                'is_active' => false,
                                'left_at' => now(),
                            ]);

                        // Crear nueva membresía
                        TeamMember::create([
                            'team_id' => $team->id,
                            'employee_id' => $employee->id,
                            'joined_at' => now(),
                            'is_active' => true,
                        ]);

                        // Actualizar relación principal en empleado
                        $employee->update([
                            'team_id' => $team->id,
                            'parent_id' => ($team->supervisor_id && (int) $employee->id !== (int) $team->supervisor_id)
                                ? $team->supervisor_id
                                : $employee->parent_id,
                        ]);

                        $results['transfers']++;
                    }

                    // Asegurar que cisco_username esté poblado si se encontró por username
                    if (empty($employee->cisco_username)) {
                        $employee->update(['cisco_username' => $userData['loginId']]);
                    }

                    $results['synced']++;
                });

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error("Error syncing Cisco User {$userData['loginId']}: ".$e->getMessage());
            }
        }

        return $results;
    }
}
