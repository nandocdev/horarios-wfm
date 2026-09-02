<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Reglas de validación de contraseñas para el CoreModule.
 *
 * Política institucional: mínimo 8 caracteres con complejidad.
 * Alineado con OWASP/NIST 800-63B.
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
            Password::min(8),
            'confirmed',
        ];
    }

    /**
     * Reglas para cambio de contraseña por administrador.
     * No requiere confirmación (un solo campo) y es opcional.
     * Cuando el campo viene vacío se autogenera o se mantiene la clave actual.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function adminPasswordRules(): array
    {
        return [
            'nullable',
            'string',
            Password::min(8),
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
