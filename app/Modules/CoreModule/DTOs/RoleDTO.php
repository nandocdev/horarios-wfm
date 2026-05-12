<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\DTOs;

/**
 * DTO para la transferencia de datos de Roles institucionales.
 */
readonly class RoleDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public int $hierarchy_level,
        public string $guard_name = 'web',
    ) {}

    /**
     * Crea un DTO desde un array (útil para formularios simples).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            code: strtoupper($data['code']),
            hierarchy_level: (int) $data['hierarchy_level'],
            guard_name: $data['guard_name'] ?? 'web'
        );
    }
}
