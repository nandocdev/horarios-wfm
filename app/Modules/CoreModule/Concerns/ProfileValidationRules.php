<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Concerns;

use App\Modules\CoreModule\Models\User;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación de perfiles de usuario para el CoreModule.
 *
 * Centraliza la validación de name y email para usuarios institucionales,
 * incluyendo validación de unicidad con soporte para ignore() en actualizaciones.
 */
trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique(User::class)->ignore($userId),
        ];
    }
}
