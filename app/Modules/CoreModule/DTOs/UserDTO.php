<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\DTOs;

/**
 * DTO para la transferencia de datos del modelo User.
 */
readonly class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public bool $is_active = true,
        public bool $force_password_change = false,
        public array $roles = [],
        public array $metadata = []
    ) {}

    /**
     * Crea un DTO desde un array (útil para Livewire Forms).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
            force_password_change: (bool) ($data['force_password_change'] ?? false),
            roles: $data['roles'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }
}
