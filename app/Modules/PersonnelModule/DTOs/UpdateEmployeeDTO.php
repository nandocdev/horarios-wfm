<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para actualizar un empleado.
 */
readonly class UpdateEmployeeDTO
{
    public function __construct(
        public ?string $employee_number = null,
        public ?string $username = null,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $email = null,
        public ?string $birth_date = null,
        public ?string $gender = null,
        public ?string $blood_type = null,
        public ?string $phone = null,
        public ?string $mobile_phone = null,
        public ?string $address = null,
        public ?int $township_id = null,
        public ?int $department_id = null,
        public ?int $position_id = null,
        public ?int $employment_status_id = null,
        public ?int $parent_id = null,
        public ?int $user_id = null,
        public ?string $hire_date = null,
        public ?float $salary = null,
        public ?bool $is_active = null,
        public ?bool $is_manager = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            employee_number: $data['employee_number'] ?? null,
            username: $data['username'] ?? null,
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            email: $data['email'] ?? null,
            birth_date: $data['birth_date'] ?? null,
            gender: $data['gender'] ?? null,
            blood_type: $data['blood_type'] ?? null,
            phone: $data['phone'] ?? null,
            mobile_phone: $data['mobile_phone'] ?? null,
            address: $data['address'] ?? null,
            township_id: $data['township_id'] ?? null,
            department_id: $data['department_id'] ?? null,
            position_id: $data['position_id'] ?? null,
            employment_status_id: $data['employment_status_id'] ?? null,
            parent_id: $data['parent_id'] ?? null,
            user_id: $data['user_id'] ?? null,
            hire_date: $data['hire_date'] ?? null,
            salary: $data['salary'] ?? null,
            is_active: $data['is_active'] ?? null,
            is_manager: $data['is_manager'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
