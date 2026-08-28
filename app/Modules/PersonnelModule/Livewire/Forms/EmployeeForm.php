<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire\Forms;

use App\Modules\PersonnelModule\Enums\Gender;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EmployeeForm extends Form
{
    public string $employee_number = '';

    public string $username = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public ?string $birth_date = null;

    public ?Gender $gender = null;

    public ?string $blood_type = null;

    public ?string $phone = '';

    public ?string $mobile_phone = '';

    public ?string $address = '';

    public ?int $township_id = null;

    public ?int $department_id = null;

    public ?int $position_id = null;

    public ?int $employment_status_id = null;

    public ?int $parent_id = null;

    public ?int $user_id = null;

    public string $hire_date = '';

    public ?float $salary = null;

    public bool $is_active = true;

    public bool $is_manager = false;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:255', 'unique:employees,employee_number'],
            'username' => ['required', 'string', 'max:255', 'unique:employees,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
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
            'parent_id' => ['nullable', 'integer', 'exists:employees,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_manager' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function validationAttributes(): array
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
