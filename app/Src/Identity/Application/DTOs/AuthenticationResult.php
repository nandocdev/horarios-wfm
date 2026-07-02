<?php

declare(strict_types=1);

namespace App\Src\Identity\Application\DTOs;

use App\Src\Identity\Domain\Entities\User;

final class AuthenticationResult
{
    private function __construct(
        private readonly bool $success,
        private readonly ?User $user,
        private readonly ?string $error,
    ) {}

    public static function success(User $user): self
    {
        return new self(true, $user, null);
    }

    public static function invalidCredentials(): self
    {
        return new self(false, null, 'Credenciales inválidas.');
    }

    public static function accountDisabled(): self
    {
        return new self(false, null, 'Cuenta desactivada. Contacta al administrador.');
    }

    public static function passwordExpired(User $user): self
    {
        return new self(false, $user, 'Debes cambiar tu contraseña antes de continuar.');
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}
