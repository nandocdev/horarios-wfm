<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Requests;

use App\Modules\PersonnelModule\Enums\Gender;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida y autoriza la creación de un empleado.
 *
 * @module EmployeesModule
 *
 * @type FormRequest
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class StoreEmployeeRequest extends FormRequest
{
    /**
     * Autorización basada en Policy.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $canViewSalary = $this->user()->can('salary.view', Employee::class);
        $canEditSalary = $this->user()->can('salary.edit', Employee::class);

        return [
            'employee_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_number'),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'username'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email'),
            ],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'township_id' => ['required', 'integer', 'exists:townships,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'employment_status_id' => ['required', 'integer', 'exists:employment_statuses,id'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
                'different:id', // Evita auto-referencia
            ],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'salary' => $canEditSalary ? ['nullable', 'numeric', 'min:0', 'decimal:0,2'] : ['prohibited'],
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
