<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para actualizar un empleado.
 * Usa un array interno para rastrear qué campos fueron provistos explícitamente.
 */
class UpdateEmployeeDTO
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, bool> */
    private array $provided = [];

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
    ) {
        // Constructor vacío - no inicializar data aquí
        // Se usará fromArray() para poblar correctamente
    }

    /**
     * Construye el DTO desde un array validado (Form Request).
     * Solo marca como provistos los campos que existen en el array de entrada.
     */
    public static function fromArray(array $data): self
    {
        $dto = new self;

        // Only set fields that are present in the input array
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
                $dto->provided[$key] = true;
                $dto->data[$key] = $value;
            }
        }

        return $dto;
    }

    /**
     * Verifica si un campo fue provisto explícitamente en el request.
     */
    public function isProvided(string $field): bool
    {
        return $this->provided[$field] ?? false;
    }

    /**
     * Obtiene solo los campos que fueron provistos, incluyendo nulls explícitos.
     *
     * @return array<string, mixed>
     */
    public function getProvidedData(): array
    {
        return $this->data;
    }
}
