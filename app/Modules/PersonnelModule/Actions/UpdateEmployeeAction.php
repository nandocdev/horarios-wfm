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
            $updateData = array_filter([
                'employee_number' => $dto->employee_number,
                'username' => $dto->username,
                'first_name' => $dto->first_name,
                'last_name' => $dto->last_name,
                'email' => $dto->email,
                'birth_date' => $dto->birth_date,
                'gender' => $dto->gender,
                'blood_type' => $dto->blood_type,
                'phone' => $dto->phone,
                'mobile_phone' => $dto->mobile_phone,
                'address' => $dto->address,
                'township_id' => $dto->township_id,
                'department_id' => $dto->department_id,
                'position_id' => $dto->position_id,
                'employment_status_id' => $dto->employment_status_id,
                'parent_id' => $dto->parent_id,
                'user_id' => $dto->user_id,
                'hire_date' => $dto->hire_date,
                'salary' => $dto->salary,
                'is_active' => $dto->is_active,
                'is_manager' => $dto->is_manager,
                'metadata' => $dto->metadata,
            ], fn ($value) => $value !== null);

            $employee->update($updateData);

            event(new EmployeeUpdated($employee));

            return $employee->fresh();
        });
    }
}
