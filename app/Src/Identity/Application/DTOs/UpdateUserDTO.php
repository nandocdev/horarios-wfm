<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

use App\Src\Shared\Domain\ValueObjects\Email;

final readonly class UpdateUserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public Email $email,
        public bool $isActive,
        public bool $forcePasswordChange,
        public array $roles = [],
        public ?string $password = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            email: new Email($data['email']),
            isActive: (bool) ($data['is_active'] ?? true),
            forcePasswordChange: (bool) ($data['force_password_change'] ?? false),
            roles: $data['roles'] ?? [],
            password: $data['password'] ?? null,
        );
    }
}
