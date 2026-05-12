<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida y autoriza la actualización de un empleado.
 *
 * @module EmployeesModule
 *
 * @type FormRequest
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Autorización basada en Policy.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_number')->ignore($employee->id),
            ],
            'username' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('employees', 'username')->ignore($employee->id),
            ],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'birth_date' => ['sometimes', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'township_id' => ['sometimes', 'integer', 'exists:townships,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['sometimes', 'integer', 'exists:positions,id'],
            'employment_status_id' => ['sometimes', 'integer', 'exists:employment_statuses,id'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
                'different:id', // Evita auto-referencia
            ],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'hire_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'salary' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['boolean'],
            'is_manager' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_number' => 'número de empleado',
            'username' => 'nombre de usuario',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'birth_date' => 'fecha de nacimiento',
            'hire_date' => 'fecha de contratación',
            'township_id' => 'corregimiento',
            'department_id' => 'departamento',
            'position_id' => 'cargo',
            'employment_status_id' => 'estado laboral',
            'parent_id' => 'jefe directo',
            'user_id' => 'usuario',
            'is_active' => 'activo',
            'is_manager' => 'es gerente',
        ];
    }
}
