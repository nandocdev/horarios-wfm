<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

use App\Src\Shared\Domain\ValueObjects\Email;

final readonly class LoginDTO
{
    public function __construct(
        public Email $email,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: new Email($data['email']),
            password: $data['password'],
        );
    }
}
