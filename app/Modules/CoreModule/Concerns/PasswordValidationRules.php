<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Reglas de validación de contraseñas para el CoreModule.
 *
 * Política flexible: mínimo 5 caracteres, sin requisitos de
 * complejidad adicionales (definido según solicitud).
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
            Password::min(5),
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
