<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\DTOs\AssignEmployeesToTeamDTO;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Illuminate\Support\Facades\DB;

/**
 * Asigna empleados a un equipo.
 * Maneja bulk assignments y desassignments.
 */
class AssignEmployeesToTeamAction
{
    /**
     * Asigna empleados a un equipo.
     */
    public function assign(AssignEmployeesToTeamDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $team = Team::find($dto->teamId);
            $supervisorId = $team?->supervisor_id;

            foreach ($dto->employeeIds as $employeeId) {
                // Marcar cualquier asignación previa como inactiva
                TeamMember::where('employee_id', $employeeId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'left_at' => now()]);

                // Crear nueva asignación
                TeamMember::create([
                    'team_id' => $dto->teamId,
                    'employee_id' => $employeeId,
                    'joined_at' => now(),
                    'is_active' => true,
                ]);

                // Sincronizar supervisor (parent_id): supervisor_id es users.id,
                // parent_id es employees.id -> se resuelve el empleado del usuario.
                $supervisorEmployeeId = $supervisorId
                    ? (int) (Employee::where('user_id', $supervisorId)->value('id') ?? 0)
                    : 0;

                if ($supervisorEmployeeId && $employeeId !== $supervisorEmployeeId) {
                    Employee::where('id', $employeeId)
                        ->update(['parent_id' => $supervisorEmployeeId]);
                }
            }
        });
    }

    /**
     * Desasigna empleados de un equipo.
     */
    public function unassign(AssignEmployeesToTeamDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $team = Team::find($dto->teamId);
            $supervisorId = $team?->supervisor_id;

            TeamMember::where('team_id', $dto->teamId)
                ->whereIn('employee_id', $dto->employeeIds)
                ->where('is_active', true)
                ->update(['is_active' => false, 'left_at' => now()]);

            // Limpiar parent_id solo si coincide con el supervisor del equipo (users.id -> employees.id).
            $supervisorEmployeeId = $supervisorId
                ? (int) (Employee::where('user_id', $supervisorId)->value('id') ?? 0)
                : 0;

            if ($supervisorEmployeeId) {
                Employee::whereIn('id', $dto->employeeIds)
                    ->where('parent_id', $supervisorEmployeeId)
                    ->update(['parent_id' => null]);
            }
        });
    }
}
