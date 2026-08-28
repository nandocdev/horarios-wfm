<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\DTOs\UpdateEmployeeDTO;
use App\Modules\PersonnelModule\Events\EmployeeUpdated;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza un empleado existente en el sistema.
 *
 * @module EmployeesModule
 *
 * @type Action
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class UpdateEmployeeAction
{
    /**
     * Ejecuta la actualización del empleado.
     *
     * @param  Employee  $employee  Empleado a actualizar
     * @param  UpdateEmployeeDTO  $dto  Datos validados para actualizar
     * @return Employee Empleado actualizado
     *
     * @throws QueryException
     */
    public function execute(Employee $employee, UpdateEmployeeDTO $dto): Employee
    {
        return DB::transaction(function () use ($employee, $dto) {
            // Solo actualizar campos que fueron provistos explícitamente en el request
            // Esto permite nulificar campos (parent_id, department_id, etc.) pasando null explícitamente
            $updateData = $dto->getProvidedData();

            $employee->update($updateData);

            event(new EmployeeUpdated($employee));

            return $employee->fresh();
        });
    }
}
