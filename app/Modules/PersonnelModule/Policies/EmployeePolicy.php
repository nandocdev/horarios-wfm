<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

/**
 * Define los permisos de acceso al recurso Employee.
 * Incluye scoping por team_id para acceso restringido.
 *
 * @module EmployeesModule
 *
 * @type Policy
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class EmployeePolicy
{
    /**
     * Determina si el usuario puede ver cualquier empleado.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employees.view')
            || $user->hasPermissionTo('employees.view.others')
            || $user->hasPermissionTo('employees.view.all');
    }

    /**
     * Determina si el usuario puede ver un empleado específico.
     * Aplica scoping por team_id.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasPermissionTo('employees.view.all')) {
            return true;
        }

        if ($this->isOwn($user, $employee)) {
            return $user->hasPermissionTo('employees.view');
        }

        if ($this->isInSameTeam($user, $employee)) {
            return $user->hasPermissionTo('employees.view.others');
        }

        return false;
    }

    /**
     * Determina si el usuario puede crear empleados.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employees.create');
    }

    /**
     * Determina si el usuario puede actualizar un empleado.
     * Aplica scoping por team_id.
     */
    public function update(User $user, Employee $employee): bool
    {
        if ($user->hasPermissionTo('employees.edit.all')) {
            return true;
        }

        if ($this->isOwn($user, $employee)) {
            return $user->hasPermissionTo('employees.edit');
        }

        if ($this->isInSameTeam($user, $employee)) {
            return $user->hasPermissionTo('employees.edit.others');
        }

        return false;
    }

    /**
     * Determina si el usuario puede eliminar un empleado.
     * Aplica scoping por team_id.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if ($user->hasPermissionTo('employees.delete.all')) {
            return true;
        }

        if ($this->isOwn($user, $employee)) {
            return $user->hasPermissionTo('employees.delete');
        }

        if ($this->isInSameTeam($user, $employee)) {
            return $user->hasPermissionTo('employees.delete.others');
        }

        return false;
    }

    /**
     * Eliminación permanente (hard delete) restringida a alto privilegio.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        if ($user->hasPermissionTo('employees.force_delete.all')) {
            return true;
        }

        if ($this->isOwn($user, $employee)) {
            return $user->hasPermissionTo('employees.force_delete');
        }

        if ($this->isInSameTeam($user, $employee)) {
            return $user->hasPermissionTo('employees.force_delete.others');
        }

        return false;
    }

    /**
     * Aplica scoping a la consulta para limitar resultados por jerarquía.
     */
    public function scopeForUser(User $user, Builder $query): Builder
    {
        // 1. Acceso Total: admin, wfm o permisos globales específicos
        if ($user->hasRole(['admin', 'wfm']) ||
            $user->hasPermissionTo('employees.view.all') ||
            $user->hasPermissionTo('employees.edit.all')) {
            return $query;
        }

        // 2. Acceso Jerárquico: El supervisor ve a sus subalternos (recursivo)
        $user->loadMissing('employee');
        if ($user->employee) {
            $subordinateIds = $user->employee->getAllSubordinateIds();

            return $query->where(function ($q) use ($user, $subordinateIds) {
                $q->where('id', $user->employee->id) // Ver su propio perfil
                    ->orWhereIn('id', $subordinateIds); // Ver sus subordinados
            });
        }

        // 3. Fallback: No tiene empleado asociado, no ve a nadie
        return $query->whereRaw('1 = 0');
    }

    /**
     * Verifica si el usuario y el empleado están en el mismo team.
     */
    private function isInSameTeam(User $user, Employee $employee): bool
    {
        if (! $user->employee || ! $user->employee->team_id || ! $employee->team_id) {
            return false;
        }

        return $user->employee->team_id === $employee->team_id;
    }

    /**
     * Identifica si el empleado corresponde al usuario autenticado.
     */
    private function isOwn(User $user, Employee $employee): bool
    {
        return (int) ($user->employee?->id ?? 0) === (int) $employee->id;
    }

    /**
     * Determina si el usuario puede gestionar asignaciones de equipos.
     */
    public function manageTeamAssignments(User $user): bool
    {
        return $user->hasPermissionTo('teams.members.manage');
    }

    /**
     * Determina si el usuario puede exportar empleados.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('employees.export');
    }

    /**
     * Determina si el usuario puede importar empleados.
     */
    public function import(User $user): bool
    {
        return $user->hasPermissionTo('employees.import');
    }

    /**
     * Permisos efectivos considerando jerarquía de rol y override de admin.
     *
     * @return array<string, mixed>
     */
    public function effectivePermissions(User $user, ?Employee $employee = null): array
    {
        $hierarchyLevel = (int) ($user->roles->max('hierarchy_level') ?? 0);
        $isAdminOverride = $user->hasRole('admin') || $hierarchyLevel >= 99;

        if ($isAdminOverride) {
            return [
                'scope' => 'all',
                'hierarchy_level' => $hierarchyLevel,
                'admin_override' => true,
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_force_delete' => true,
                'can_export' => true,
            ];
        }

        $target = $employee ?? new Employee(['team_id' => $user->employee?->team_id]);
        $isOwn = $employee ? $this->isOwn($user, $employee) : false;
        $isSameTeam = $employee ? $this->isInSameTeam($user, $employee) : (bool) $user->employee?->team_id;

        $scope = 'none';
        if ($user->hasPermissionTo('employees.view.all')) {
            $scope = 'all';
        } elseif ($isOwn && $user->hasPermissionTo('employees.view')) {
            $scope = 'own';
        } elseif ($isSameTeam && $user->hasPermissionTo('employees.view.others')) {
            $scope = 'others';
        }

        return [
            'scope' => $scope,
            'hierarchy_level' => $hierarchyLevel,
            'admin_override' => false,
            'can_view' => $employee ? $this->view($user, $target) : $this->viewAny($user),
            'can_create' => $this->create($user),
            'can_update' => $employee ? $this->update($user, $target) : $user->hasPermissionTo('employees.edit') || $user->hasPermissionTo('employees.edit.others') || $user->hasPermissionTo('employees.edit.all'),
            'can_delete' => $employee ? $this->delete($user, $target) : $user->hasPermissionTo('employees.delete') || $user->hasPermissionTo('employees.delete.others') || $user->hasPermissionTo('employees.delete.all'),
            'can_force_delete' => $employee ? $this->forceDelete($user, $target) : $user->hasPermissionTo('employees.force_delete') || $user->hasPermissionTo('employees.force_delete.others') || $user->hasPermissionTo('employees.force_delete.all'),
            'can_export' => $this->export($user),
        ];
    }
    /**
     * Determina si el usuario puede ver el desempeño de un empleado específico.
     */
    public function viewPerformance(User $user, ?Employee $employee = null): bool
    {
        if ($user->hasRole(['admin', 'wfm', 'director'])) {
            return true;
        }

        if ($employee === null) {
            return $user->hasRole(['chief', 'coordinator', 'supervisor']) || $user->employee !== null;
        }

        if ($this->isOwn($user, $employee)) {
            return true;
        }

        if (($user->hasRole(['chief', 'coordinator', 'supervisor']) || $user->employee?->hasCoordinatorRights()) && $user->employee) {
            $managedTeamIds = $user->employee->getManagedTeamIds();
            
            return $user->employee->isSupervisorOf($employee->id) 
                || in_array($employee->team_id, $managedTeamIds);
        }

        return false;
    }
}
