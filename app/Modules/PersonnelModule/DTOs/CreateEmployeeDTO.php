<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para crear un empleado.
 */
readonly class CreateEmployeeDTO
{
    public function __construct(
        public string $employee_number,
        public string $username,
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $birth_date,
        public ?string $gender = null,
        public ?string $blood_type = null,
        public ?string $phone = null,
        public ?string $mobile_phone = null,
        public ?string $address = null,
        public ?int $township_id = null,
        public ?int $department_id = null,
        public int $position_id = 0,
        public int $employment_status_id = 0,
        public ?int $parent_id = null,
        public int $user_id = 0,
        public string $hire_date = '',
        public ?float $salary = null,
        public bool $is_active = true,
        public bool $is_manager = false,
        public ?array $metadata = null,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            employee_number: $data['employee_number'],
            username: $data['username'],
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            email: $data['email'],
            birth_date: $data['birth_date'],
            gender: $data['gender'] ?? null,
            blood_type: $data['blood_type'] ?? null,
            phone: $data['phone'] ?? null,
            mobile_phone: $data['mobile_phone'] ?? null,
            address: $data['address'] ?? null,
            township_id: $data['township_id'],
            department_id: $data['department_id'] ?? null,
            position_id: $data['position_id'],
            employment_status_id: $data['employment_status_id'],
            parent_id: $data['parent_id'] ?? null,
            user_id: $data['user_id'],
            hire_date: $data['hire_date'],
            salary: $data['salary'] ?? null,
            is_active: $data['is_active'] ?? true,
            is_manager: $data['is_manager'] ?? false,
            metadata: $data['metadata'] ?? null,
        );
    }
}
