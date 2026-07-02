<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

use App\Src\Shared\Domain\ValueObjects\Email;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public Email $email,
        public string $password,
        public bool $isActive = true,
        public bool $forcePasswordChange = false,
        public array $roles = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: new Email($data['email']),
            password: $data['password'],
            isActive: (bool) ($data['is_active'] ?? true),
            forcePasswordChange: (bool) ($data['force_password_change'] ?? false),
            roles: $data['roles'] ?? [],
        );
    }
}
