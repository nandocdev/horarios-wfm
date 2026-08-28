<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Observers;

use App\Modules\AnalyticsModule\Models\EmployeeSnapshot;
use App\Modules\PersonnelModule\Models\Employee;

/**
 * Observa el ciclo de vida del modelo Employee.
 * Efectos secundarios: creación automática de snapshots SCD2 en analytics_employee_snapshot
 * cuando cambian los datos organizacionales (departamento, puesto, supervisor, equipo).
 *
 * @module EmployeesModule
 *
 * @author GitHub Copilot
 *
 * @created 2026-08-21
 */
class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        $this->createSnapshot($employee);
    }

    public function updated(Employee $employee): void
    {
        // Solo crear snapshot si hay cambios en datos organizacionales
        $changed = $employee->isDirty([
            'department_id',
            'position_id',
            'team_id',
            'supervisor_id',
            'employment_status_id',
        ]);

        if ($changed) {
            $this->createSnapshot($employee);
        }
        // No crear snapshot duplicado si no hay cambios organizacionales
    }

    public function deleting(Employee $employee): void
    {
        if ($employee->isForceDeleting()) {
            EmployeeSnapshot::where('employee_id', $employee->id)->delete();
        }
    }

    public function deleted(Employee $employee): void
    {
        if (! $employee->isForceDeleting()) {
            // Marcar snapshot ACTUAL como inactivo
            EmployeeSnapshot::where('employee_id', $employee->id)
                ->where('is_current', true)
                ->update(['is_current' => false, 'valid_to' => now()]);
        }
    }

    public function restored(Employee $employee): void
    {
        // Reactivar snapshot actual creando uno nuevo con estado actual
        $this->createSnapshot($employee);
    }

    public function forceDeleted(Employee $employee): void
    {
        EmployeeSnapshot::where('employee_id', $employee->id)->delete();
    }

    /**
     * Crear o actualizar snapshot SCD2 para el empleado.
     */
    protected function createSnapshot(Employee $employee): void
    {
        // Verificar si ya existe un snapshot actual para este empleado
        $existing = EmployeeSnapshot::where('employee_id', $employee->id)
            ->where('is_current', true)
            ->first();

        $validFrom = now()->startOfDay();

        if ($existing) {
            // Actualizar snapshot existente: setear valid_to a ayer y crear uno nuevo
            $existing->valid_to = $validFrom->subDay();
            $existing->is_current = false;
            $existing->save();

            // Crear nuevo snapshot
            EmployeeSnapshot::create([
                'employee_id' => $employee->id,
                'valid_from' => $validFrom,
                'valid_to' => null,
                'is_current' => true,
                'team_id' => $employee->team_id,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'supervisor_id' => $employee->parent_id,
                'employment_status_id' => $employee->employment_status_id,
                'is_active' => $employee->is_active ?? true,
                'metadata' => [
                    'changed_at' => now()->toISOString(),
                    'changed_reason' => 'employee_updated',
                ],
            ]);
        } else {
            // Crear nuevo snapshot SCD2
            EmployeeSnapshot::create([
                'employee_id' => $employee->id,
                'valid_from' => $validFrom,
                'valid_to' => null,
                'is_current' => true,
                'team_id' => $employee->team_id,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'supervisor_id' => $employee->parent_id,
                'employment_status_id' => $employee->employment_status_id,
                'is_active' => $employee->is_active ?? true,
                'metadata' => [
                    'created_at' => now()->toISOString(),
                    'change_type' => 'initial',
                ],
            ]);
        }
    }
}
