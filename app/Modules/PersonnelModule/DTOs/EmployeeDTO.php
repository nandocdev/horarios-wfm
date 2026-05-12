<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

use App\Modules\PersonnelModule\Models\Employee;

/**
 * Datos completos de un empleado para transferencia.
 */
readonly class EmployeeDTO
{
    public function __construct(
        public int $id,
        public string $employee_number,
        public string $username,
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $birth_date,
        public ?string $gender,
        public ?string $blood_type,
        public ?string $phone,
        public ?string $mobile_phone,
        public ?string $address,
        public int $township_id,
        public ?int $department_id,
        public int $position_id,
        public int $employment_status_id,
        public ?int $parent_id,
        public int $user_id,
        public string $hire_date,
        public ?float $salary,
        public bool $is_active,
        public bool $is_manager,
        public ?array $metadata,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    /**
     * Construye el DTO desde un modelo Employee.
     */
    public static function fromModel(Employee $employee): self
    {
        return new self(
            id: $employee->id,
            employee_number: $employee->employee_number,
            username: $employee->username,
            first_name: $employee->first_name,
            last_name: $employee->last_name,
            email: $employee->email,
            birth_date: $employee->birth_date->format('Y-m-d'),
            gender: $employee->gender,
            blood_type: $employee->blood_type,
            phone: $employee->phone,
            mobile_phone: $employee->mobile_phone,
            address: $employee->address,
            township_id: $employee->township_id,
            department_id: $employee->department_id,
            position_id: $employee->position_id,
            employment_status_id: $employee->employment_status_id,
            parent_id: $employee->parent_id,
            user_id: $employee->user_id,
            hire_date: $employee->hire_date->format('Y-m-d'),
            salary: $employee->salary,
            is_active: $employee->is_active,
            is_manager: $employee->is_manager,
            metadata: $employee->metadata,
            created_at: $employee->created_at?->format('Y-m-d H:i:s'),
            updated_at: $employee->updated_at?->format('Y-m-d H:i:s'),
        );
    }
}
