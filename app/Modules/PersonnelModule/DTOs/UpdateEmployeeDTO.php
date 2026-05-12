<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para actualizar un empleado.
 */
readonly class UpdateEmployeeDTO
{
    public function __construct(
        public ?string $employee_number,
        public ?string $username,
        public ?string $first_name,
        public ?string $last_name,
        public ?string $email,
        public ?string $birth_date,
        public ?string $gender,
        public ?string $blood_type,
        public ?string $phone,
        public ?string $mobile_phone,
        public ?string $address,
        public ?int $township_id,
        public ?int $department_id,
        public ?int $position_id,
        public ?int $employment_status_id,
        public ?int $parent_id,
        public ?int $user_id,
        public ?string $hire_date,
        public ?float $salary,
        public ?bool $is_active,
        public ?bool $is_manager,
        public ?array $metadata,
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
