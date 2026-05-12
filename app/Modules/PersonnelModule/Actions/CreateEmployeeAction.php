<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\DTOs\CreateEmployeeDTO;
use App\Modules\PersonnelModule\Events\EmployeeCreated;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crea un nuevo empleado en el sistema.
 *
 * @module EmployeesModule
 *
 * @type Action
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class CreateEmployeeAction
{
    /**
     * Ejecuta la creación del empleado.
     *
     * @param  CreateEmployeeDTO  $dto  Datos validados del empleado
     * @return Employee Empleado creado y persistido
     *
     * @throws QueryException
     */
    public function execute(CreateEmployeeDTO $dto): Employee
    {
        return DB::transaction(function () use ($dto) {
            $employee = Employee::create([
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
            ]);

            event(new EmployeeCreated($employee));

            return $employee;
        });
    }
}
