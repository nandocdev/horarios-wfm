<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Reglas de validación de contraseñas para el CoreModule.
 *
 * Policies institucionales de complejidad de contraseñas:
 * - Mínimo 8 caracteres
 * - Mínimo 1 mayúscula, 1 minúscula, 1 número
 * - No debe contener el nombre de usuario
 * - Expiración forzada cada 90 días
 */
trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(8)
                ->mixedCase(1)
                ->numbers(1)
                ->uncompromised(),
            'confirmed',
        ];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
